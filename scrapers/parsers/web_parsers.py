import datetime
import json
import re
from typing import List, Dict, Any, Optional
from bs4 import BeautifulSoup
from urllib.parse import urljoin
import httpx

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
    is_outside_brno,
)


def parse_mendelu(html: str, base_url: str = "https://mendelu.cz/akce/") -> List[Dict[str, Any]]:
    """
    Parses events from mendelu.cz/akce/
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year
    now = datetime.datetime.now()
    seen_urls = set()

    for item in soup.select("li.event-card__item, .event-card"):
        card = item if item.name == "a" else item.find("a", class_="event-card")
        if not card:
            continue

        full_card_text = card.get_text(" ", strip=True)
        if is_administrative_or_irrelevant(full_card_text):
            continue

        url = urljoin(base_url, card.get("href", ""))
        if url in seen_urls:
            continue
        seen_urls.add(url)

        heading_el = card.find(["h3", "h2"], class_=lambda c: c and "heading" in c)
        if not heading_el:
            heading_el = card.find(["h3", "h2", "strong"])
        if not heading_el:
            continue
        title = heading_el.get_text(strip=True)

        # Classification of .event-card__info-item elements:
        # Items can be:
        # 1. Date string (e.g. "24. 11. 2026" or "2. 11. 2026 – 30. 11. 2026")
        # 2. Time string (e.g. "9:00 – 15:00" or "15:00")
        # 3. Location string (e.g. "ZF MENDELU, Valtická 337, Lednice", "Q48", "Budova M, místnost M2.12")
        info_items = []
        for i in card.find_all(class_=lambda c: c and "info-item" in c):
            t = i.get_text(separator=" ", strip=True)
            t = re.sub(r"Svg Vector Icons\s*:?\s*https?://\S+", "", t, flags=re.I).strip()
            t = re.sub(r"\s+", " ", t.replace("\xa0", " ")).strip()
            if t:
                info_items.append(t)

        time_str = None
        date_item_str = None
        loc_str = None

        for inf in info_items:
            is_time = bool(
                re.search(r"\b\d{1,2}:\d{2}\b", inf)
                and not re.search(r"\b\d{4}\b", inf)
                and not re.search(r"\d{1,2}\.\s*\d{1,2}\.", inf)
            )
            is_date = bool(
                re.search(r"\b\d{4}\b", inf)
                or re.search(r"\d{1,2}\.\s*\d{1,2}\.", inf)
                or any(m in inf.lower() for m in CZECH_MONTHS)
            )

            if is_time and not time_str:
                time_str = inf
            elif is_date and not date_item_str:
                date_item_str = inf
            elif not loc_str:
                loc_str = inf

        # Date parsing
        day, month, year = None, None, current_year
        end_day, end_month, end_year = None, None, current_year

        # Priority 1: From explicit date info item (e.g. "24. 11. 2026" or "2. 11. 2026 – 30. 11. 2026")
        if date_item_str:
            dates = re.findall(r"(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})", date_item_str)
            if dates:
                day = int(dates[0][0])
                month = int(dates[0][1])
                year = int(dates[0][2])
                if len(dates) > 1:
                    end_day = int(dates[1][0])
                    end_month = int(dates[1][1])
                    end_year = int(dates[1][2])

        # Priority 2: From calendar badge .event-card__date-day and .event-card__date-month
        if not day or not month:
            day_el = card.find(class_=lambda c: c and "day" in c)
            month_el = card.find(class_=lambda c: c and "month" in c)
            day_matches = re.findall(r"\d+", day_el.get_text()) if day_el else []
            day = int(day_matches[0]) if day_matches else None
            if len(day_matches) > 1:
                end_day = int(day_matches[1])
            month_str = month_el.get_text(strip=True).lower() if month_el else ""
            month = CZECH_MONTHS.get(month_str[:3], datetime.datetime.now().month)
            if month < datetime.datetime.now().month - 1:
                year += 1

        if not day or not month:
            day = 20
            month = 11

        # Time parsing
        start_hour, start_min = 10, 0
        end_hour, end_min = None, None
        has_explicit_time = False

        if time_str:
            time_matches = re.findall(r"\b(\d{1,2}):(\d{2})\b", time_str)
            if time_matches:
                start_hour = int(time_matches[0][0])
                start_min = int(time_matches[0][1])
                has_explicit_time = True
                if len(time_matches) > 1:
                    end_hour = int(time_matches[1][0])
                    end_min = int(time_matches[1][1])

        starts_at = f"{year:04d}-{month:02d}-{day:02d} {start_hour:02d}:{start_min:02d}:00"
        
        ends_at = None
        if end_hour is not None and end_min is not None:
            e_year = end_year if end_year else year
            e_month = end_month if end_month else month
            e_day = end_day if end_day else day
            ends_at = f"{e_year:04d}-{e_month:02d}-{e_day:02d} {end_hour:02d}:{end_min:02d}:00"
        elif end_day and 1 <= end_day <= 31:
            e_year = end_year if end_year else year
            e_month = end_month if end_month else month
            ends_at = f"{e_year:04d}-{e_month:02d}-{end_day:02d} 18:00:00"

        # Skip past events older than 7 days
        try:
            event_dt = datetime.datetime.strptime(starts_at[:10], "%Y-%m-%d")
            if event_dt < (now - datetime.timedelta(days=7)):
                continue
        except Exception:
            pass

        # Location: preserve specific room and attach Brno campus address if not explicitly present
        if not loc_str:
            loc_str = "Areál MENDELU, Zemědělská 1, Brno"
        elif "brno" not in loc_str.lower() and not re.search(r"\b\d{1,4}\s*brno", loc_str.lower()):
            loc_str = f"{loc_str}, Areál MENDELU, Zemědělská 1, Brno"

        desc = f"Akce Mendelovy univerzity v Brně: {title}."
        if info_items:
            desc += " " + " | ".join(info_items)

        # Skip events outside Brno (e.g. Lednice, Valtice)
        if is_outside_brno(loc_str, title + " " + desc):
            continue

        # Cover image extraction from .event-card__image or style background-image
        cover_image = None
        img_el = card.find(class_=lambda c: c and "image" in c)
        if img_el:
            style = img_el.get("style", "")
            bg_match = re.search(r"url\s*\(\s*['\"]?([^'\")]+)['\"]?\s*\)", style)
            if bg_match:
                cover_image = urljoin(base_url, bg_match.group(1).strip())
            elif img_el.name == "img":
                cover_image = urljoin(base_url, img_el.get("src", ""))

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": ends_at,
            "is_all_day": not has_explicit_time and not time_str,
            "location_name": loc_str,
            "cover_image": cover_image,
            "source_url": url,
            "source_type": "scraper_web",
        })

    return events


def parse_fit_vut(html: str, base_url: str = "https://www.fit.vut.cz/fit/calendar/") -> List[Dict[str, Any]]:
    """
    Parses events from fit.vut.cz/fit/calendar/
    Extracts high-resolution images, rich descriptions, exact times, rooms, and venues.
    Fetches online details when available and supports offline parsing without network.
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year
    now = datetime.datetime.now()

    # Reuse an HTTP client for detail requests if online
    client = None
    if base_url.startswith("http"):
        try:
            client = httpx.Client(
                headers={
                    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) BrnoEvents/1.0",
                    "Accept-Language": "cs,en;q=0.9",
                },
                timeout=6.0,
                follow_redirects=True,
            )
        except Exception:
            client = None

    try:
        items = list(soup.select("li.c-events__item, .c-events__item"))

        # If online and in Q4 (months 10-12), check next year link (e.g. /fit/calendar/2027/)
        if client and now.month >= 10:
            next_year = current_year + 1
            for page_link in soup.select("a.pagination__link, .pagination a"):
                href = page_link.get("href", "")
                if str(next_year) in href:
                    try:
                        next_url = urljoin(base_url, href)
                        resp_next = client.get(next_url)
                        if resp_next.status_code == 200:
                            soup_next = BeautifulSoup(resp_next.text, "lxml")
                            for ni in soup_next.select("li.c-events__item, .c-events__item"):
                                items.append(ni)
                    except Exception:
                        pass
                    break

        seen_urls = set()

        for item in items:
            link_el = item.find("a")
            if not link_el:
                continue

            url = urljoin(base_url, link_el.get("href", ""))
            if url in seen_urls:
                continue
            seen_urls.add(url)

            title_el = item.find(["h2", "h3"], class_=lambda c: c and "title" in c)
            if not title_el:
                title_el = item.find(["h2", "h3", "h4"])
            if not title_el:
                continue
            raw_title = title_el.get_text(strip=True)
            title = clean_title(raw_title)

            if is_administrative_or_irrelevant(title):
                continue

            # Cover image from thumbnail
            img_el = item.select_one(".b-term__img img, img")
            cover_image = None
            if img_el and img_el.get("src"):
                cover_image = urljoin(base_url, img_el.get("src"))

            # Initial date/time from item
            starts_at = None
            ends_at = None
            has_explicit_time = False

            time_el = item.find("time")
            if time_el and time_el.get("datetime"):
                dt_raw = time_el.get("datetime").strip()
                try:
                    dt_parsed = datetime.datetime.fromisoformat(dt_raw)
                    if "T" in dt_raw or ":" in dt_raw:
                        starts_at = dt_parsed.strftime("%Y-%m-%d %H:%M:%S")
                        has_explicit_time = True
                    else:
                        starts_at = dt_parsed.strftime("%Y-%m-%d")
                except Exception:
                    pass

            if not starts_at:
                day_el = item.find(class_=lambda c: c and "day" in c)
                month_el = item.find(class_=lambda c: c and "month" in c)
                year_el = item.find(class_=lambda c: c and "year" in c)

                day_matches = re.findall(r"\d+", day_el.get_text()) if day_el else []
                day = int(day_matches[0]) if day_matches else None
                end_day = int(day_matches[1]) if len(day_matches) > 1 else None

                month_str = month_el.get_text(strip=True).lower() if month_el else ""
                month = CZECH_MONTHS.get(month_str[:3], now.month)
                year = int(re.sub(r"\D", "", year_el.get_text())) if year_el and re.sub(r"\D", "", year_el.get_text()) else current_year

                if day and month:
                    starts_at = f"{year:04d}-{month:02d}-{day:02d}"
                    if end_day and 1 <= end_day <= 31:
                        ends_at = f"{year:04d}-{month:02d}-{end_day:02d} 18:00:00"
                else:
                    starts_at = f"{current_year:04d}-11-20"

            # Skip past events older than 7 days
            try:
                event_dt = datetime.datetime.strptime(starts_at[:10], "%Y-%m-%d")
                if event_dt < (now - datetime.timedelta(days=7)):
                    continue
            except Exception:
                pass

            # Detail extraction
            desc = f"Událost Fakulty informačních technologií VUT v Brně: {title}."
            short_desc = desc[:200]
            location_name = "FIT VUT, Božetěchova 1/2, Brno – Královo Pole"
            price_type = "free"  # Default for university academic/public events
            price_amount = None

            # Fetch rich detail if online and matching detail URL pattern
            if client and re.search(r"fit\.vut\.cz/fit/calendar/\d+", url):
                try:
                    r_det = client.get(url)
                    if r_det.status_code == 200:
                        d_soup = BeautifulSoup(r_det.text, "lxml")
                        og_img = d_soup.select_one('meta[property="og:image"], meta[name="twitter:image"]')
                        if og_img and og_img.get("content"):
                            cover_image = og_img.get("content").strip()

                        body_el = d_soup.select_one(".b-detail__content, .b-detail__body")
                        if body_el:
                            for annot in body_el.select(".b-detail__annot, .c-news"):
                                annot.decompose()
                            body_text = body_el.get_text("\n", strip=True)

                            if is_administrative_or_irrelevant(body_text):
                                continue

                            paragraphs = [p.get_text(" ", strip=True) for p in body_el.find_all(["p", "li"]) if p.get_text(" ", strip=True)]
                            clean_paras = [
                                p for p in paragraphs
                                if not re.match(r"^[📅📍📌🕘💰🔗]|^(?:Kdy|Kde|Místo|Rozsah|Lektor|Cena|Vložil|Poslední změna)\s*:", p, re.I)
                            ]
                            if clean_paras:
                                full_body = "\n\n".join(clean_paras)
                                if len(full_body) > 30:
                                    desc = full_body
                                    short_desc = clean_paras[0][:200]

                            s_time, e_time = extract_time_range_from_text(body_text)
                            if not s_time:
                                s_time = extract_explicit_time_from_text(body_text)
                            if s_time:
                                sh, sm = s_time
                                starts_at = f"{starts_at[:10]} {sh:02d}:{sm:02d}:00"
                                has_explicit_time = True
                                if e_time:
                                    eh, em = e_time
                                    ends_at = f"{starts_at[:10]} {eh:02d}:{em:02d}:00"

                            v_name, v_addr = extract_venue_and_address(body_text, default_venue=location_name)
                            if v_name:
                                location_name = v_name

                            p_type, p_val = extract_price_info(body_text)
                            if p_type:
                                price_type = p_type
                                price_amount = p_val
                except Exception:
                    pass

            if len(starts_at) == 10:
                starts_at = f"{starts_at} 14:00:00"

            # Check if outside Brno
            if is_outside_brno(location_name, title + " " + desc):
                continue

            events.append({
                "title": title,
                "short_description": short_desc,
                "description": desc,
                "starts_at": starts_at,
                "ends_at": ends_at,
                "is_all_day": not has_explicit_time,
                "cover_image": cover_image,
                "location_name": location_name,
                "price_type": price_type,
                "price_amount": price_amount,
                "source_url": url,
                "source_type": "scraper_web",
            })
    finally:
        if client:
            try:
                client.close()
            except Exception:
                pass

    return events


def parse_unob(html: str, base_url: str = "https://unob.cz/univerzita/media-a-verejnost/akce-univerzity-obrany/") -> List[Dict[str, Any]]:
    """
    Parses events from UNOB public events page
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year

    for p in soup.select("p, li"):
        text = p.get_text(strip=True)
        if is_administrative_or_irrelevant(text):
            continue
        # Matches patterns like: "5. prosince 2026 – Dny otevřených dveří..." or "16. ledna 2027 –..."
        match = re.match(r"^(\d{1,2})\.\s*([a-zA-Zá-žÁ-Ž]+)\s*(\d{4})?\s*[–—-]\s*(.+)$", text)
        if not match:
            continue

        day = int(match.group(1))
        month_str = match.group(2).lower()
        year_str = match.group(3)
        title = match.group(4).strip()

        month = CZECH_MONTHS.get(month_str, None)
        if not month:
            continue

        year = int(year_str) if year_str else current_year

        now = datetime.datetime.now()
        event_dt = datetime.datetime(year, month, day)
        if event_dt < (now - datetime.timedelta(days=7)):
            continue

        # UNOB has faculties in Hradec Králové (VLF) and Vyškov (ÚOPZHN), and holds military shows across CZ.
        # LiveBrno strictly includes Brno events only!
        if is_outside_brno("", title) or not ("brno" in title.lower() or "fvt" in title.lower() or "fvl" in title.lower() or "kasárn" in title.lower() or "kounicova" in title.lower()):
            continue

        loc = "Areál Univerzity obrany (Kasárna Černá Pole / Šumavská), Brno"
        starts_at = f"{year:04d}-{month:02d}-{day:02d} 09:00:00"
        url = base_url + f"#{abs(hash(title + starts_at))}"

        desc = f"Událost Univerzity obrany: {title}."

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": None,
            "is_all_day": True,
            "location_name": loc,
            "source_url": url,
            "source_type": "scraper_web",
        })

    return events


def _format_muni_iso_dt(dt_str: Optional[str], default_time: str = "10:00:00") -> Optional[str]:
    """Helper to convert ISO 8601 strings to YYYY-MM-DD HH:MM:SS format."""
    if not dt_str:
        return None
    dt_str = dt_str.strip()
    if "T" in dt_str:
        main_part = dt_str.split("T")[0]
        time_part = dt_str.split("T")[1].split("+")[0].split("-")[0].replace("Z", "")
        if len(time_part.split(":")) == 2:
            time_part += ":00"
        return f"{main_part} {time_part[:8]}"
    else:
        return f"{dt_str} {default_time}"


def parse_muni(html: str, base_url: str = "https://www.muni.cz/kalendar") -> List[Dict[str, Any]]:
    """
    Parses events from muni.cz/kalendar (.box-event).
    Extracts structured JSON-LD and detail data when online, with fallback to card HTML.
    Filters out CŽV courses, administrative procedures, secondary school events, and non-Brno items.
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year

    # Use a single HTTP client if fetching details online
    client = None
    if base_url.startswith("http"):
        try:
            client = httpx.Client(
                headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) BrnoEvents/1.0"},
                timeout=6.0,
                follow_redirects=True,
            )
        except Exception:
            client = None

    try:
        for item in soup.select(".box-event"):
            # Remove any decorative badge like "TOP AKCE" or ribbons
            for badge in item.select(".box-event__badge, .badge, .ribbon"):
                badge.decompose()

            full_card_text = item.get_text(" ", strip=True)
            if is_administrative_or_irrelevant(full_card_text):
                continue

            link_el = item.find("a")
            href = link_el.get("href", "") if link_el else ""
            if "is.muni.cz/obchod" in href or "vzdelavani-po-cely-zivot" in href:
                continue

            url = urljoin(base_url, href) if href else base_url

            title_el = item.select_one(".box-event__title")
            if not title_el:
                title_el = item.find(["h4", "h3", "h2"])
            if not title_el:
                continue

            # Clone and decompose icons / sub-elements to avoid dirty title
            t_copy = BeautifulSoup(str(title_el), "lxml")
            for sub in t_copy.find_all(["span", "small", "i", "div"]):
                sub.decompose()
            raw_title = t_copy.get_text(" ", strip=True)
            title = clean_title(raw_title)

            if len(title) < 4:
                continue

            # Extract image if present on card
            img_el = item.select_one(".box-event__img img, img")
            cover_image = None
            if img_el and img_el.get("src"):
                cover_image = urljoin(base_url, img_el.get("src"))

            # Card date and teaser fallback
            date_el = item.select_one(".box-event__date")
            date_str = date_el.get_text(" ", strip=True) if date_el else ""

            content_el = item.select_one(".box-event__content")
            desc = ""
            if content_el:
                c_copy = BeautifulSoup(str(content_el), "lxml")
                for rm in c_copy.select(".box-event__title, .box-event__date, .box-event__tags, .meta, span, h4"):
                    rm.decompose()
                desc = c_copy.get_text(" ", strip=True)
            desc = re.sub(r"\btop\s*akce:?\s*", "", desc, flags=re.IGNORECASE).strip()

            starts_at = None
            ends_at = None
            loc_cand = None
            is_all_day = False
            tags_text = ""

            # Attempt fetching rich detail page if available and online
            should_fetch_detail = client and (
                "muni.cz/kalendar/u/" in url or
                (url.startswith("http") and not any(skip in url for skip in ["is.muni.cz", "facebook.com", "instagram.com", "muni.cz/kalendar"]))
            )
            if should_fetch_detail:
                try:
                    d_resp = client.get(url)
                    if d_resp.status_code == 200:
                        d_soup = BeautifulSoup(d_resp.text, "lxml")
                        # 1. Parse JSON-LD schema
                        for sc in d_soup.find_all("script", type="application/ld+json"):
                            try:
                                d = json.loads(sc.string or "")
                                if isinstance(d, dict) and d.get("@type") == "Event":
                                    if d.get("startDate"):
                                        is_all_day = "T" not in str(d.get("startDate"))
                                        starts_at = _format_muni_iso_dt(str(d.get("startDate")), "10:00:00")
                                    if d.get("endDate"):
                                        ends_at = _format_muni_iso_dt(str(d.get("endDate")), "23:59:59")
                                    loc = d.get("location")
                                    loc_cand = loc.get("name") if isinstance(loc, dict) else str(loc or "")
                                    if loc_cand:
                                        loc_cand = re.sub(r"<[^>]+>", "", loc_cand).strip()
                                    break
                            except Exception:
                                pass

                        # 2. Rich meta description
                        meta_desc = d_soup.find("meta", property="og:description") or d_soup.find("meta", attrs={"name": "description"})
                        if meta_desc and meta_desc.get("content") and len(meta_desc.get("content").strip()) > len(desc):
                            desc = meta_desc.get("content").strip()

                        # 3. Rich meta image
                        meta_img = d_soup.find("meta", property="og:image")
                        if meta_img and meta_img.get("content") and not cover_image:
                            cover_image = meta_img.get("content").strip()

                        # 4. Tags
                        tag_els = d_soup.select(".box-event-detail2__tags span, .tag")
                        if tag_els:
                            tags_text = " ".join(t.get_text(" ", strip=True) for t in tag_els)

                        # 5. Venue element fallback
                        if not loc_cand:
                            v_el = d_soup.select_one(".box-event-detail2__venues, [class*=\"venues\"]")
                            if v_el:
                                loc_cand = v_el.get_text(" ", strip=True).replace("Zobrazit mapu", "").strip()

                        # 6. For external landing pages, detect venue or time directly from page body
                        page_text = " ".join(d_soup.get_text(" ", strip=True).split())
                        if not loc_cand:
                            v_found, _ = extract_venue_and_address(page_text, default_venue="")
                            if v_found and v_found not in ["Brno", "Brno centrum"]:
                                loc_cand = v_found

                        if not starts_at:
                            time_match = re.search(r"\b(\d{1,2}:\d{2})\b", page_text[:1500])
                            if time_match:
                                t_val = time_match.group(1)
                                t_h, t_m = t_val.split(":")
                                if int(t_h) <= 23 and int(t_m) <= 59:
                                    start_time = f"{int(t_h):02d}:{t_m}:00"
                except Exception:
                    pass

            # Fallback date & time parsing from card if detail was unavailable
            if not starts_at and date_str:
                times = re.findall(r"(\d{1,2}:\d{2})", date_str)
                if times:
                    t_h = int(times[0].split(":")[0])
                    t_m = times[0].split(":")[1]
                    start_time = f"{t_h:02d}:{t_m}:00"
                    if len(times) > 1:
                        e_h = int(times[1].split(":")[0])
                        e_m = times[1].split(":")[1]
                        end_time = f"{e_h:02d}:{e_m}:00"
                    else:
                        end_time = None
                else:
                    start_time = "10:00:00"
                    end_time = None

                m_range = re.search(r"^\s*(\d{1,2})\.\s*[–—-]\s*(\d{1,2})\.\s*(\d{1,2})\.", date_str)
                if m_range:
                    start_day = int(m_range.group(1))
                    start_month = int(m_range.group(3))
                    end_day = int(m_range.group(2))
                    end_month = int(m_range.group(3))
                else:
                    dates = re.findall(r"(\d{1,2})\.\s*(\d{1,2})\.", date_str)
                    if dates:
                        start_day = int(dates[0][0])
                        start_month = int(dates[0][1])
                        if len(dates) > 1:
                            end_day = int(dates[1][0])
                            end_month = int(dates[1][1])
                        else:
                            end_day, end_month = None, None
                    else:
                        start_day, start_month = 15, 11
                        end_day, end_month = None, None

                year = current_year
                now = datetime.datetime.now()
                if start_month < now.month - 1:
                    year += 1

                starts_at = f"{year:04d}-{start_month:02d}-{start_day:02d} {start_time}"
                if end_day and end_month:
                    end_year = year
                    if end_month < start_month:
                        end_year += 1
                    e_time = end_time if end_time else "23:59:59"
                    ends_at = f"{end_year:04d}-{end_month:02d}-{end_day:02d} {e_time}"
                elif end_time:
                    ends_at = f"{year:04d}-{start_month:02d}-{start_day:02d} {end_time}"

            if not starts_at:
                continue

            # Skip irrelevant or foreign locations (e.g. fairs in Germany, events outside Brno like Telč)
            if loc_cand and is_administrative_or_irrelevant(loc_cand):
                continue
            if is_outside_brno(loc_cand or "", title + " " + desc):
                continue
            if loc_cand and any(w in loc_cand.lower() for w in ["německo", "germany", "austria", "rakousko", "slovakia", "polsko"]):
                continue

            if not desc:
                desc = f"Událost Masarykovy univerzity: {title} ({date_str})."

            # Determine venue & address (use extracted loc_cand, otherwise Brno fallback)
            loc_input = loc_cand if loc_cand else ""
            venue_display, _ = extract_venue_and_address(loc_input, default_venue="Brno")

            # Determine category & price
            cat_slug = detect_event_category(f"{title} {tags_text} {desc}")
            price_type, price_amount = extract_price_info(desc)

            events.append({
                "title": title,
                "short_description": desc[:200],
                "description": desc,
                "starts_at": starts_at,
                "ends_at": ends_at,
                "is_all_day": is_all_day,
                "cover_image": cover_image,
                "location_name": venue_display,
                "category_slug": cat_slug,
                "price_type": price_type,
                "price_amount": price_amount,
                "source_url": url,
                "source_type": "scraper_web",
            })
    finally:
        if client:
            client.close()

    return events


def parse_generic_html(html: str, base_url: str) -> List[Dict[str, Any]]:
    """
    Fallback parser for generic web pages using Schema.org Event JSON-LD or microdata.
    """
    import json
    soup = BeautifulSoup(html, "lxml")
    events = []

    # Check for ld+json
    for script in soup.find_all("script", type="application/ld+json"):
        try:
            data = json.loads(script.string or "")
            items = data if isinstance(data, list) else [data]
            for it in items:
                if it.get("@type") == "Event":
                    events.append({
                        "title": it.get("name", "Bez názvu"),
                        "short_description": it.get("description", "")[:200],
                        "description": it.get("description", ""),
                        "starts_at": it.get("startDate", "2026-11-20 18:00:00"),
                        "ends_at": it.get("endDate"),
                        "location_name": it.get("location", {}).get("name") if isinstance(it.get("location"), dict) else str(it.get("location", "")),
                        "source_url": it.get("url") or base_url,
                        "source_type": "scraper_web",
                    })
        except Exception:
            continue

    return events
