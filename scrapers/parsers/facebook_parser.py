import asyncio
import base64
import hashlib
import json
import os
import re
import urllib.request
from datetime import datetime
from typing import Any, Dict, List, Optional
import httpx
from bs4 import BeautifulSoup
from playwright.async_api import async_playwright

from parsers.common import (
    CZECH_MONTHS,
    clean_emoji,
    clean_title,
    normalize_text,
    extract_time_range_from_text,
    extract_explicit_time_from_text,
    extract_explicit_date_from_text,
    extract_venue_and_address,
    extract_price_info,
    detect_event_category,
    is_administrative_or_irrelevant,
)


def get_openai_api_key() -> Optional[str]:
    """Retrieves OPENAI_API_KEY from environment or .env file."""
    key = os.environ.get("OPENAI_API_KEY")
    if key:
        return key

    env_path = "/root/brno-events/.env"
    if os.path.exists(env_path):
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line.startswith("OPENAI_API_KEY="):
                    val = line.split("=", 1)[1].strip().strip('"').strip("'")
                    if val:
                        return val
    return None


def download_cover_image(img_url: str, event_id: str) -> Optional[str]:
    """Downloads Facebook event cover image and saves it to local public storage."""
    try:
        url_hash = hashlib.md5(img_url.encode('utf-8')).hexdigest()[:8]
        filename = f"fb_{event_id}_{url_hash}.jpg"
        relative_path = f"events/covers/{filename}"

        public_covers_dir = "/root/brno-events/public/storage/events/covers"
        storage_covers_dir = "/root/brno-events/storage/app/public/events/covers"
        os.makedirs(public_covers_dir, exist_ok=True)
        os.makedirs(storage_covers_dir, exist_ok=True)

        public_dest = os.path.join(public_covers_dir, filename)
        storage_dest = os.path.join(storage_covers_dir, filename)

        if os.path.exists(public_dest) and os.path.getsize(public_dest) > 20000:
            return relative_path

        headers = {
            "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36"
        }
        req = urllib.request.Request(img_url, headers=headers)
        with urllib.request.urlopen(req, timeout=15) as resp:
            content_type = resp.headers.get("Content-Type", "")
            if not content_type.startswith("image/"):
                return None
            data = resp.read()
            if len(data) < 1000:
                return None
            with open(public_dest, "wb") as f:
                f.write(data)
            with open(storage_dest, "wb") as f:
                f.write(data)

        return relative_path
    except Exception:
        return None


def parse_facebook_date(text: str) -> Optional[datetime]:
    """Parses date and time from Czech Facebook event text strings."""
    if not text:
        return None

    # Try explicit date extraction
    dt = extract_explicit_date_from_text(text)
    if dt:
        time_res = extract_explicit_time_from_text(text)
        h = time_res[0] if time_res else 18
        m = time_res[1] if time_res else 0
        return dt.replace(hour=h, minute=m, second=0)

    # Pattern 2: [month_word] DD YYYY (common in og:description: 'listopad 11 2026')
    m2 = re.search(r'([a-záčďéěíňóřšťúůýž]+)\s+(\d{1,2})\s+(\d{4})', text, re.IGNORECASE)
    if m2:
        month_word = m2.group(1).lower()
        month = CZECH_MONTHS.get(month_word)
        day = int(m2.group(2))
        year = int(m2.group(3))
        if month and 1 <= day <= 31:
            time_res = extract_explicit_time_from_text(text)
            h = time_res[0] if time_res else 18
            m = time_res[1] if time_res else 0
            return datetime(year, month, day, h, m)

    return None


def detect_category(text: str) -> str:
    """Classifies event category from Czech keywords."""
    return detect_event_category(text)
    lower = text.lower()
    if any(k in lower for k in ['party', 'párty', 'mecheche', 'dnb', 'tep', 'rave', 'dj', 'warmup', 'ples', 'večírek']):
        return 'party-kluby'
    if any(k in lower for k in ['workshop', 'přednášk', 'prednask', 'seminář', 'debata', 'diskuze', 'kurz', 'školení']):
        return 'prednasky-workshopy'
    if any(k in lower for k in ['běh', 'turnaj', 'fotbal', 'volejbal', 'sport', 'futsal', 'beerpong']):
        return 'sport'
    if any(k in lower for k in ['výstava', 'vernisáž', 'divadlo', 'kino', 'promítání', 'koncert', 'jam']):
        return 'kultura-umeni'
    if any(k in lower for k in ['veletrh', 'kariér', 'networking', 'hackathon', 'job']):
        return 'kariera-networking'
    return 'ostatni'


async def scrape_single_facebook_event(
    page,
    event_url: str,
    university: str = "OTHER",
    fallback_image_url: Optional[str] = None,
) -> Optional[Dict[str, Any]]:
    """Visits a single Facebook event page and extracts structured data."""
    try:
        await page.goto(event_url, wait_until='networkidle', timeout=15000)
        content = await page.content()
        soup = BeautifulSoup(content, 'html.parser')

        # Title
        og_title = soup.find('meta', property='og:title')
        raw_title = og_title['content'] if og_title else await page.title()
        title = clean_emoji(re.sub(r'\s*\|\s*Facebook$', '', raw_title).strip())
        if not title or title.lower() == 'facebook':
            return None

        # og:description and og:image
        og_desc = soup.find('meta', property='og:description')
        og_desc_text = og_desc['content'] if og_desc else ''

        og_img = soup.find('meta', property='og:image')
        img_url = og_img['content'] if og_img else None

        # Event ID
        event_id_match = re.search(r'/events/(\d+)', event_url)
        event_id = event_id_match.group(1) if event_id_match else hashlib.md5(event_url.encode()).hexdigest()[:10]

        # High-res cover photo from event page HTML (cover_photo in Relay GraphQL state)
        high_res_img_url = None
        m_cover = re.search(r'\"cover_photo\":\{.*?\"(?:full_image|viewer_image|image)\":\{.*?\"uri\":\"(https?:\\\\?/\\\\?/[^\"]+)\"', content)
        if m_cover:
            high_res_img_url = m_cover.group(1).replace('\\/', '/').replace('&amp;', '&')
        else:
            m_cover2 = re.search(r'\"cover_photo\":\{.*?\"uri\":\"(https?:\\\\?/\\\\?/[^\"]+)\"', content)
            if m_cover2:
                high_res_img_url = m_cover2.group(1).replace('\\/', '/').replace('&amp;', '&')
            else:
                m_cover3 = re.search(r'\"uri\":\"(https?:\\\\?/\\\\?/scontent[^\"]*(?:1920|1080|960)[^\"]*)\"', content)
                if m_cover3:
                    high_res_img_url = m_cover3.group(1).replace('\\/', '/').replace('&amp;', '&')

        # Download cover image (Priority: high-res cover_photo -> og:image -> fallback thumbnail)
        cover_image = None
        if high_res_img_url:
            cover_image = download_cover_image(high_res_img_url, event_id)
        if not cover_image and img_url:
            cover_image = download_cover_image(img_url, event_id)
        if not cover_image and fallback_image_url:
            cover_image = download_cover_image(fallback_image_url, event_id)

        # Extract body lines
        lines = [line.strip() for line in (await page.inner_text('body')).splitlines() if line.strip()]

        # Ticket URL
        ticket_url = None
        ticket_services = ['goout.net', 'smsticket.cz', 'fairplay.events', 'boomticketing', 'ticketmaster', 'ticketstream']
        for a in soup.find_all('a', href=True):
            h = a['href']
            if any(svc in h for svc in ticket_services):
                ticket_url = h
                break

        if not ticket_url:
            for i, line in enumerate(lines):
                if 'vstupenk' in line.lower() and i + 1 < len(lines):
                    next_line = lines[i + 1]
                    if any(svc in next_line for svc in ticket_services) or next_line.startswith('http') or next_line.startswith('www.'):
                        ticket_url = next_line if next_line.startswith('http') else 'https://' + next_line
                        break

        # Date & Time parsing
        starts_at = None
        for line in lines[15:45]:
            dt = parse_facebook_date(line)
            if dt:
                starts_at = dt
                break

        if not starts_at and og_desc_text:
            starts_at = parse_facebook_date(og_desc_text)

        # Location name & address using centralized extractor
        location_name, address = extract_venue_and_address("\n".join(lines[20:55]), default_venue="Brno")
        if location_name == "Brno" and og_desc_text:
            alt_loc, alt_addr = extract_venue_and_address(og_desc_text, default_venue="Brno")
            if alt_loc != "Brno":
                location_name, address = alt_loc, alt_addr

        # Extract clean description
        desc_lines = []
        start_collecting = False
        stop_tokens = ['navrhované události', 'soukromí', 'zobrazit víc na facebooku', 'pořadatel', 'vstupenky']

        for line in lines:
            low = line.lower()
            if any(term in low for term in ['kdokoli na facebooku', 'veřejná']):
                start_collecting = True
                continue
            if start_collecting:
                if any(st in low for st in stop_tokens):
                    break
                if any(term in low for term in ['ulice', 'třída', 'náměstí', 'česko', 'czech republic']):
                    break
                if line.strip() == 'Brno' or line.strip() == location_name:
                    continue
                clean = line.replace('… Zobrazit víc', '').replace('Zobrazit víc', '').strip()
                if clean and len(clean) > 3:
                    desc_lines.append(clean)

        description = "\n\n".join(desc_lines)
        if not description:
            description = og_desc_text

        description = clean_emoji(description)
        short_description = description[:200] + "..." if len(description) > 200 else description

        if not starts_at:
            starts_at = datetime.now()

        starts_at_str = starts_at.strftime("%Y-%m-%d %H:%M:%S")
        ends_at_str = None

        category_slug = detect_category(title + " " + description)
        price_type = "paid" if ticket_url or 'vstup' in description.lower() else "free"

        return {
            "title": title,
            "short_description": short_description,
            "description": description,
            "starts_at": starts_at_str,
            "ends_at": ends_at_str,
            "is_all_day": False,
            "location_name": location_name,
            "venue_address": address,
            "cover_image": cover_image,
            "ticket_url": ticket_url,
            "facebook_event_url": event_url,
            "source_url": event_url,
            "source_type": "facebook",
            "price_type": price_type,
            "category_slug": category_slug,
            "university": university,
        }
    except Exception:
        return None


async def parse_facebook_events_async(
    source_url_or_slug: str,
    university: str = "OTHER",
    limit: int = 5,
    use_ai: bool = False,
) -> List[Dict[str, Any]]:
    """Scrapes upcoming Facebook events from a public Facebook page using Playwright."""
    clean_slug = source_url_or_slug.strip().rstrip('/')
    if 'facebook.com/' in clean_slug:
        clean_slug = clean_slug.split('facebook.com/')[-1].split('/')[0]

    events_page_url = f"https://www.facebook.com/{clean_slug}/events"

    events: List[Dict[str, Any]] = []

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        context = await browser.new_context(
            user_agent="Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36",
            viewport={"width": 1280, "height": 800},
            locale="cs-CZ"
        )
        page = await context.new_page()

        try:
            await page.goto(events_page_url, wait_until="networkidle", timeout=15000)
            links = await page.query_selector_all('a[href*="/events/"]')
            unique_events = []
            seen_ids = set()

            for link in links:
                href = await link.get_attribute("href")
                if not href:
                    continue
                clean_href = href.split("?")[0].rstrip('/')
                m = re.search(r'/events/(\d+)', clean_href)
                if m:
                    eid = m.group(1)
                    if eid not in seen_ids:
                        seen_ids.add(eid)
                        # Extract card image if available in parent
                        card_img_url = None
                        parent = link
                        for _ in range(5):
                            parent = await parent.query_selector('xpath=..')
                            if not parent:
                                break
                            p_img = await parent.query_selector('img[src*="scontent"], img[src*="fbcdn"]')
                            if p_img:
                                card_img_url = await p_img.get_attribute('src')
                                break

                        unique_events.append({
                            "url": f"https://www.facebook.com/events/{eid}/",
                            "card_img": card_img_url,
                        })

            for item in unique_events[:limit]:
                ev_data = await scrape_single_facebook_event(
                    page,
                    item["url"],
                    university=university,
                    fallback_image_url=item.get("card_img"),
                )
                if ev_data:
                    events.append(ev_data)

        finally:
            await browser.close()

    return events


def extract_event_from_text(text: str, base_url: str = "", university: str = "OTHER") -> Optional[Dict[str, Any]]:
    """Helper for parsing Facebook event description/text offline without launching Playwright."""
    lines = [line.strip() for line in text.splitlines() if line.strip()]
    if not lines:
        return None

    title = clean_title(lines[0])
    dt = extract_explicit_date_from_text(text)
    time_start, time_end = extract_time_range_from_text(text)

    hour = time_start[0] if time_start else 18
    minute = time_start[1] if time_start else 0

    starts_at_str = None
    if dt:
        starts_at_str = dt.replace(hour=hour, minute=minute, second=0).strftime("%Y-%m-%d %H:%M:%S")

    ends_at_str = None
    if dt and time_end:
        ends_at_str = dt.replace(hour=time_end[0], minute=time_end[1], second=0).strftime("%Y-%m-%d %H:%M:%S")

    location_name, address = extract_venue_and_address(text, default_venue="Brno")
    price_type, price_amount = extract_price_info(text)
    cat = detect_event_category(text)

    return {
        "title": title,
        "starts_at": starts_at_str,
        "ends_at": ends_at_str,
        "is_all_day": False,
        "location_name": location_name,
        "venue_address": address,
        "price_type": price_type,
        "price_amount": price_amount,
        "category_slug": cat,
        "short_description": text[:200],
        "description": text,
        "source_url": base_url,
        "source_type": "scraper_facebook",
        "university": university,
    }


def parse_facebook(
    source_url_or_slug: str,
    university: str = "OTHER",
    limit: int = 5,
    use_ai: bool = False,
) -> List[Dict[str, Any]]:
    """Synchronous wrapper for parse_facebook_events_async."""
    return asyncio.run(
        parse_facebook_events_async(
            source_url_or_slug=source_url_or_slug,
            university=university,
            limit=limit,
            use_ai=use_ai,
        )
    )
