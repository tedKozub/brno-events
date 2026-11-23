import asyncio
import hashlib
import json
import os
import re
import shutil
import subprocess
import sys
import urllib.request
from datetime import datetime, timedelta
from typing import Any, Dict, List, Optional, Tuple
from playwright.async_api import async_playwright

from parsers.common import (
    CZECH_MONTHS,
    clean_emoji,
    clean_title,
    normalize_text,
    extract_explicit_time_from_text,
    extract_explicit_date_from_text,
    extract_time_range_from_text,
    extract_venue_and_address,
    extract_price_info,
    detect_event_category,
    is_administrative_or_irrelevant,
)

CZECH_DAY_MAP = {
    'pondělí': 0, 'pondělky': 0, 'pondeli': 0, 'pondelky': 0, 'pondělní': 0, 'pondelni': 0,
    'úterý': 1, 'utery': 1, 'úterní': 1, 'uterni': 1,
    'středa': 2, 'středy': 2, 'středu': 2, 'streda': 2, 'stredy': 2, 'stredu': 2, 'středeční': 2, 'stredecni': 2,
    'čtvrtek': 3, 'čtvrtky': 3, 'ctvrtek': 3, 'ctvrtky': 3, 'čtvrteční': 3, 'ctvrtecni': 3,
    'pátek': 4, 'pátky': 4, 'patek': 4, 'patky': 4, 'páteční': 4, 'patecni': 4,
    'sobota': 5, 'soboty': 5, 'sobotu': 5, 'sobotní': 5, 'sobotni': 5,
    'neděle': 6, 'nedele': 6, 'neděli': 6, 'nedeli': 6, 'nedělní': 6, 'nedelni': 6,
}

CZECH_WEEKDAY_NAMES = {
    0: 'pondělí',
    1: 'úterý',
    2: 'středa',
    3: 'čtvrtek',
    4: 'pátek',
    5: 'sobota',
    6: 'neděle'
}


def resolve_next_weekday(target_weekday: int, hour: int = 18, minute: int = 0, now: Optional[datetime] = None) -> datetime:
    """Calculates the upcoming date for the given weekday (0=Monday..6=Sunday)."""
    if now is None:
        now = datetime.now()
    days_ahead = (target_weekday - now.weekday()) % 7
    if days_ahead == 0:
        event_time_today = now.replace(hour=hour, minute=minute, second=0, microsecond=0)
        if event_time_today <= now:
            days_ahead = 7
    return (now + timedelta(days=days_ahead)).replace(hour=hour, minute=minute, second=0, microsecond=0)


def extract_weekday_from_text(text: str) -> Optional[int]:
    """Detects if text mentions a Czech weekday (e.g. 'středy', 've středu', 'čtvrtek')."""
    lower = text.lower()
    for word, day_idx in CZECH_DAY_MAP.items():
        if re.search(r'\b' + re.escape(word) + r'\b', lower):
            return day_idx
    return None


def extract_explicit_time_from_text(text: str) -> Optional[Tuple[int, int]]:
    """Extracts explicit hour and minute from text without confusing with dates."""
    # Match colon time e.g. 19:00, v 19:00, od 18:30
    m = re.search(r"\b(?:v|ve|od|at)?\s*(\d{1,2}):(\d{2})\b", text, re.IGNORECASE)
    if m:
        h, mn = int(m.group(1)), int(m.group(2))
        if 0 <= h <= 23 and 0 <= mn <= 59:
            return h, mn
    # Match dot time with explicit time preposition e.g. 'v 19.00' or 'od 18.30'
    m = re.search(r"\b(?:v|ve|od|at)\s*(\d{1,2})\.(\d{2})\b", text, re.IGNORECASE)
    if m:
        h, mn = int(m.group(1)), int(m.group(2))
        if 0 <= h <= 23 and 0 <= mn <= 59:
            return h, mn
    # Match dot time with hour unit suffix e.g. '19.00 hod' or '18.30h'
    m = re.search(r"\b(\d{1,2})\.(\d{2})\s*(?:hod|h|hodin)\b", text, re.IGNORECASE)
    if m:
        h, mn = int(m.group(1)), int(m.group(2))
        if 0 <= h <= 23 and 0 <= mn <= 59:
            return h, mn
    # Match hour with preposition and unit e.g. 'v 19h', 'od 18 hodin'
    m = re.search(r"\b(?:v|ve|od|at)\s*(\d{1,2})\s*(?:h|hod|hodin)\b", text, re.IGNORECASE)
    if m:
        h = int(m.group(1))
        if 0 <= h <= 23:
            return h, 0
    return None


def extract_explicit_date_from_text(text: str, default_year: Optional[int] = None) -> Optional[datetime]:
    """Extracts explicit Czech date (DD.MM.YYYY or DD.MM. or DD. [czech_month] [YYYY]) from text."""
    if default_year is None:
        default_year = datetime.now().year

    # 1. Pattern with Czech month word: e.g. 10. listopadu 2026, 25. listopadu
    month_pattern = "|".join(re.escape(k) for k in CZECH_MONTHS.keys())
    m_word = re.search(r"\b(\d{1,2})\.\s*(" + month_pattern + r")(?:\s+(\d{4}))?\b", text, re.IGNORECASE)
    if m_word:
        day = int(m_word.group(1))
        month_str = m_word.group(2).lower()
        month = CZECH_MONTHS.get(month_str)
        year = int(m_word.group(3)) if m_word.group(3) else default_year
        if month and 1 <= day <= 31:
            try:
                return datetime(year, month, day)
            except ValueError:
                pass

    # 2. Pattern DD.MM.YYYY or DD.MM.
    m_num = re.search(r"(?<!\d)(\d{1,2})\.\s*(\d{1,2})\.(?:\s*(\d{4}))?(?!\d)", text)
    if m_num:
        day = int(m_num.group(1))
        month = int(m_num.group(2))
        year = int(m_num.group(3)) if m_num.group(3) else default_year
        if not m_num.group(3) and month < datetime.now().month - 1:
            year += 1
        if 1 <= month <= 12 and 1 <= day <= 31:
            try:
                return datetime(year, month, day)
            except ValueError:
                pass

    return None


def sanitize_extracted_event_date(event: Dict[str, Any], raw_text: str, now: Optional[datetime] = None) -> Optional[Dict[str, Any]]:
    """Validates and corrects extracted starts_at against explicit dates in caption/flyer.

    CRUCIAL: Events MUST have an explicit calendar date (e.g. 10.11.2026, 18.11., 25. listopadu).
    If a post mentions only recurring days like 'středy' or 'každé úterý' without an explicit date,
    we cannot determine which date it refers to, so such events MUST be ignored (return None).
    """
    if not event or "starts_at" not in event or not event["starts_at"]:
        return None

    if now is None:
        now = datetime.now()

    starts_at_str = str(event["starts_at"])
    try:
        dt = datetime.strptime(starts_at_str[:19], "%Y-%m-%d %H:%M:%S")
    except ValueError:
        try:
            dt = datetime.fromisoformat(starts_at_str.replace("Z", "+00:00"))
        except Exception:
            return None

    title = event.get("title") or ""
    desc = event.get("description") or ""
    combined_text = f"{title} {desc} {raw_text}"

    explicit_date = extract_explicit_date_from_text(combined_text, default_year=dt.year)
    extracted_time = extract_explicit_time_from_text(combined_text)

    # 1. Explicit date written in caption/flyer ALWAYS takes absolute precedence
    if explicit_date is not None:
        target_hour = extracted_time[0] if extracted_time else dt.hour
        target_minute = extracted_time[1] if extracted_time else dt.minute
        corrected = explicit_date.replace(hour=target_hour, minute=target_minute, second=0)
        event["starts_at"] = corrected.strftime("%Y-%m-%d %H:%M:%S")
        return event

    # 2. If NO explicit date exists in text, but recurring weekday is mentioned (e.g. beerpong 'středy'),
    # we cannot determine which date it refers to, so such events MUST be ignored (return None).
    if extract_weekday_from_text(raw_text) is not None:
        return None

    # 3. If dt was extracted directly from the graphic flyer image (multimodal vision),
    # preserve the exact date from the flyer without any shifting or guessing.
    if dt is not None and event.get("title"):
        event["starts_at"] = dt.strftime("%Y-%m-%d %H:%M:%S")
        return event

    return None


AGY_VISION_SCHEMA = {
    "type": "object",
    "properties": {
        "title": {
            "type": "string",
            "description": "Presny nazev akce z plakatu (bez emoji)",
        },
        "starts_at": {
            "type": ["string", "null"],
            "description": "Datum a cas zacatku YYYY-MM-DD HH:MM:SS",
        },
        "ends_at": {
            "type": ["string", "null"],
            "description": "Datum a cas konce YYYY-MM-DD HH:MM:SS nebo null",
        },
        "is_all_day": {
            "type": "boolean",
        },
        "location_name": {
            "type": "string",
            "description": "Presny nazev klubu, kavarny, fakulty nebo salu v Brne",
        },
        "price_type": {
            "type": "string",
            "enum": ["free", "paid", "voluntary"],
        },
        "price_amount": {
            "type": ["integer", "null"],
        },
        "category_slug": {
            "type": "string",
            "enum": ["party-kluby", "prednasky-workshopy", "sport", "kultura-umeni", "kariera-networking"],
        },
        "short_description": {
            "type": "string",
            "description": "Strucne shrnuti akce pro studenty na 1-2 vety bez emoji",
        },
        "description": {
            "type": "string",
            "description": "Kompletni popis a program bez emoji",
        },
    },
    "required": ["title", "starts_at", "location_name", "price_type", "category_slug"],
}


def extract_with_agy_vision(
    image_path: str,
    caption: str,
    model: str = "gemini-3.8-flash-low",
    timeout_sec: int = 30,
) -> Optional[Dict[str, Any]]:
    """Uses local agy CLI (Gemini Flash Low) to extract structured event details from poster image + caption."""
    if not shutil.which("agy"):
        return None

    if not os.path.exists(image_path):
        return None

    try:
        now = datetime.now()
        today_cz = CZECH_WEEKDAY_NAMES.get(now.weekday(), "")

        prompt = f"""Jsi expert na brnensky studentsky zivot. Analyzuj prilozeny graficky plakat {image_path} a text prispevku z Instagramu.
Dnesni datum je: {now.strftime('%Y-%m-%d')} ({today_cz}).

Extrahuj informace o udalosti a vrat strukturovany JSON podle zadaneho schematu.

STRIKTNI PRAVIDLA PRO DATUM (starts_at):
1. AKCI EXTRAHUJ POUZE V PRIPADE, ZE JE NA PLAKATU NEBO V POPISKU UVEDENO KONKRETNI KALENDARNI DATUM (napr. 11.11., 18.11., 25. listopadu, 4.3.2027).
   - POUZIJ PRESNE TOTO DATUM Z PLAKATU CI POPISKU BEZ JAKEHOKOLIV DOMYSLENI CI POSOUVANI V CASE!
   - Format data a casu: YYYY-MM-DD HH:MM:SS.
   - A TO I V PRIPADE, ZE DATUM Z PLAKATU JE JIZ V MINULOSTI VZHLEDEM K DNESKU ({now.strftime('%Y-%m-%d')})! Pokud je na plakatu 11.11.2026, MUSIS vratit 2026-11-11 a NIKDY ho neposouvat!
2. POKUD JE NA PLAKATU NEBO V TEXTU UVEDEN POUZE DEN V TYDNU NEBO OPAKOVANI (napr. 'stredy', 'kazde utery', 'ctvrtek') BEZ KONKRETNIHO KALENDARNIHO DATA, VRAT "starts_at": null, PROTOZE AKCE BEZ KONKRETNIHO KALENDARNIHO DATA IGNORUJEME.
3. Cas vyber dle plakatu a textu (napr. 16:00 -> 16:00:00). Pokud cas chybi, pouzij 18:00:00.

Popisek z Instagramu:
<untrusted_event_text>
{caption}
</untrusted_event_text>
"""
        cmd = [
            "agy",
            "-p", prompt,
            "--model", model,
            "--json-schema", json.dumps(AGY_VISION_SCHEMA, ensure_ascii=False),
            "--output-format", "json",
            "--print-timeout", f"{timeout_sec}s",
            "--disable-slash-commands",
            "--sandbox",
        ]

        proc = subprocess.run(cmd, capture_output=True, text=True, timeout=timeout_sec + 10)
        if proc.returncode != 0:
            sys.stderr.write(f"Warning: agy vision exited with code {proc.returncode}: {proc.stderr}\n")
            return None

        data = json.loads(proc.stdout)
        extracted = data.get("structured_output")
        if not extracted and "response" in data:
            resp_text = data.get("response", "")
            if "```json" in resp_text:
                resp_text = resp_text.split("```json")[1].split("```")[0].strip()
            extracted = json.loads(resp_text)

        if not extracted or not extracted.get("starts_at"):
            return None

        extracted["title"] = clean_emoji(extracted.get("title", ""))
        extracted["location_name"] = clean_emoji(extracted.get("location_name", "Brno"))
        extracted["short_description"] = clean_emoji(extracted.get("short_description", ""))
        if "description" in extracted:
            extracted["description"] = clean_emoji(extracted["description"])

        return sanitize_extracted_event_date(extracted, caption, now=now)

    except subprocess.TimeoutExpired:
        sys.stderr.write(f"Warning: agy vision timed out after {timeout_sec}s\n")
    except Exception as e:
        sys.stderr.write(f"Warning: agy vision extraction failed: {e}\n")

    return None


def extract_event_from_caption(caption: str, base_url: str) -> Optional[Dict[str, Any]]:
    """Heuristic / NLP fallback to extract structured event fields from caption."""
    cleaned = caption.strip()
    if not cleaned or len(cleaned) < 20:
        return None

    lines = [l.strip() for l in cleaned.split("\n") if l.strip()]
    if not lines:
        return None

    # Title: typically the first clean line
    raw_title = lines[0]
    title = clean_emoji(raw_title)
    title = re.sub(r"^(?:Je tu|Přichází|Zveme vás na:?)\s+", "", title, flags=re.IGNORECASE).strip()
    if len(title) < 4 and len(lines) > 1:
        title = clean_emoji(lines[1])
    if len(title) > 90:
        title = title[:90].rsplit(" ", 1)[0]

    # Date & Time parsing
    now = datetime.now()
    explicit_date = extract_explicit_date_from_text(cleaned, default_year=now.year)
    if explicit_date is None:
        # Vague recurring events without an explicit calendar date (e.g. beerpong "středy") are ignored
        return None

    extracted_time = extract_explicit_time_from_text(cleaned)
    hour = extracted_time[0] if extracted_time else 18
    minute = extracted_time[1] if extracted_time else 0
    starts_at = explicit_date.replace(hour=hour, minute=minute, second=0).strftime("%Y-%m-%d %H:%M:%S")

    # Venue & address using shared extractor
    venue, _ = extract_venue_and_address(cleaned, default_venue="Brno")

    # Price using shared extractor
    price_type, price_amount = extract_price_info(cleaned)

    cat_slug = "kultura-umeni"
    lower_c = cleaned.lower()
    if any(k in lower_c for k in ["beerpong", "turnaj", "závod", "běh", "sport", "liga"]):
        cat_slug = "sport"
    elif any(k in lower_c for k in ["workshop", "přednáška", "seminář", "školení", "bylink"]):
        cat_slug = "prednasky-workshopy"
    elif any(k in lower_c for k in ["ples", "párty", "party", "bečka", "jam", "dj", "noc"]):
        cat_slug = "party-kluby"
    elif any(k in lower_c for k in ["kariér", "networking", "stáž", "práce", "veletrh"]):
        cat_slug = "kariera-networking"

    first_paragraph = clean_emoji(lines[0])
    if len(lines) > 1 and len(first_paragraph) < 80:
        first_paragraph += " " + clean_emoji(lines[1])
    short_desc = first_paragraph[:250].strip()

    return {
        "title": title,
        "starts_at": starts_at,
        "ends_at": None,
        "is_all_day": False,
        "location_name": venue,
        "price_type": price_type,
        "price_amount": price_amount,
        "category_slug": cat_slug,
        "short_description": short_desc,
        "description": clean_emoji(cleaned),
        "source_url": base_url,
        "source_type": "scraper_instagram",
    }


async def scrape_instagram_profile_async(
    username_or_url: str,
    output_dir: str = "/root/brno-events/public/storage/events/covers",
    limit: int = 10,
) -> List[Dict[str, Any]]:
    """Scrapes Instagram profile posts and posters via the public embed viewport."""
    clean_name = username_or_url.strip().rstrip("/")
    if "/" in clean_name:
        clean_name = clean_name.split("/")[-1]
    if clean_name.startswith("@"):
        clean_name = clean_name[1:]

    embed_url = f"https://www.instagram.com/{clean_name}/embed/"
    profile_url = f"https://www.instagram.com/{clean_name}/"

    os.makedirs(output_dir, exist_ok=True)
    events = []

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=True)
        page = await browser.new_page(
            viewport={"width": 800, "height": 1200},
            user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
        )

        try:
            resp = await page.goto(embed_url, wait_until="networkidle", timeout=20000)
            if not resp or resp.status != 200:
                return []

            imgs = await page.locator("img[alt]").all()

            for i, img in enumerate(imgs):
                if len(events) >= limit:
                    break

                src = await img.get_attribute("src")
                alt = await img.get_attribute("alt")

                if not alt or "profile picture" in alt.lower() or not src:
                    continue

                # Download flyer image first (strip dynamic tokens from CDN URL for stable hash)
                clean_src = src.split("?")[0]
                img_hash = hashlib.md5(clean_src.encode("utf-8")).hexdigest()[:12]
                filename = f"ig_{clean_name}_{img_hash}.jpg"
                dest_path = os.path.join(output_dir, filename)

                if not os.path.exists(dest_path):
                    try:
                        urllib.request.urlretrieve(src, dest_path)
                    except Exception:
                        pass

                # 1. Fast path: extract structured event from caption if explicit date/details exist
                event = extract_event_from_caption(alt, base_url=profile_url)

                # 2. Vision path: if caption lacks explicit date or venue, use local agy multimodal vision on the flyer image
                if not event and os.path.exists(dest_path):
                    event = extract_with_agy_vision(dest_path, alt)

                if not event:
                    continue

                event = sanitize_extracted_event_date(event, alt)
                if not event or not event.get("starts_at"):
                    continue

                if os.path.exists(dest_path):
                    event["cover_image"] = f"events/covers/{filename}"
                    event["original_image_url"] = src
                    try:
                        from PIL import Image
                        with Image.open(dest_path) as im:
                            w, h = im.size
                            if h > 1.15 * w:
                                event["cover_image_type"] = "poster"
                            elif w >= 1.2 * h:
                                event["cover_image_type"] = "banner"
                            else:
                                event["cover_image_type"] = "article"
                    except Exception:
                        event["cover_image_type"] = "banner"

                event["source_url"] = f"{profile_url}#post-{img_hash}"
                event["source_type"] = "scraper_instagram"
                event["instagram_post_url"] = profile_url
                events.append(event)

        finally:
            await browser.close()

    return events


def parse_instagram(username_or_url: str, limit: int = 10) -> List[Dict[str, Any]]:
    """Synchronous entrypoint for Instagram scraper."""
    return asyncio.run(scrape_instagram_profile_async(username_or_url, limit=limit))

