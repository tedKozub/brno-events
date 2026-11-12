import datetime
import re
from typing import List, Dict, Any, Optional
from bs4 import BeautifulSoup
from urllib.parse import urljoin

CZECH_MONTHS = {
    'led': 1, 'ledna': 1, 'leden': 1,
    'úno': 2, 'února': 2, 'únor': 2,
    'bře': 3, 'března': 3, 'březen': 3,
    'dub': 4, 'dubna': 4, 'duben': 4,
    'kvě': 5, 'května': 5, 'květen': 5,
    'čer': 6, 'června': 6, 'červen': 6,
    'čvc': 7, 'července': 7, 'červenec': 7,
    'srp': 8, 'srpna': 8, 'srpen': 8,
    'zář': 9, 'září': 9,
    'říj': 10, 'října': 10, 'říjen': 10,
    'lis': 11, 'listopadu': 11, 'listopad': 11,
    'pro': 12, 'prosince': 12, 'prosinec': 12,
    'january': 1, 'february': 2, 'march': 3, 'april': 4,
    'may': 5, 'june': 6, 'july': 7, 'august': 8,
    'september': 9, 'october': 10, 'november': 11, 'december': 12
}


def parse_mendelu(html: str, base_url: str = "https://mendelu.cz/akce/") -> List[Dict[str, Any]]:
    """
    Parses events from mendelu.cz/akce/
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year

    for item in soup.select("li.event-card__item, .event-card"):
        card = item if item.name == "a" else item.find("a", class_="event-card")
        if not card:
            continue

        url = urljoin(base_url, card.get("href", ""))
        heading_el = card.find(["h3", "h2"], class_=lambda c: c and "heading" in c)
        if not heading_el:
            heading_el = card.find(["h3", "h2", "strong"])
        if not heading_el:
            continue
        title = heading_el.get_text(strip=True)

        # Date parsing
        day_el = card.find(class_=lambda c: c and "day" in c)
        month_el = card.find(class_=lambda c: c and "month" in c)
        day_matches = re.findall(r"\d+", day_el.get_text()) if day_el else []
        day = int(day_matches[0]) if day_matches else None
        end_day = int(day_matches[1]) if len(day_matches) > 1 else None
        month_str = month_el.get_text(strip=True).lower() if month_el else ""
        month = CZECH_MONTHS.get(month_str[:3], datetime.datetime.now().month)

        year = current_year
        if month < datetime.datetime.now().month - 1:
            year += 1

        ends_at = None
        if day and month:
            starts_at = f"{year:04d}-{month:02d}-{day:02d} 10:00:00"
            if end_day and 1 <= end_day <= 31:
                ends_at = f"{year:04d}-{month:02d}-{end_day:02d} 18:00:00"
        else:
            starts_at = f"{current_year:04d}-11-20 10:00:00"

        # Details/location
        clean_info = []
        for i in card.find_all(class_=lambda c: c and "info-item" in c):
            t = i.get_text(separator=" ", strip=True)
            t = re.sub(r"Svg Vector Icons\s*:?\s*https?://\S+", "", t, flags=re.I).strip()
            if t:
                clean_info.append(t)

        loc_str = "Areál MENDELU, Zemědělská 1, Brno"
        for inf in clean_info:
            if any(k in inf.lower() for k in ["brno", "budova", "učebna", "pavilon", "sál", "kampus", "lab", "zemědělská"]):
                loc_str = inf
                break

        desc = f"Akce Mendelovy univerzity v Brně: {title}."
        if clean_info:
            desc += " " + " | ".join(clean_info)

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": None,
            "is_all_day": False,
            "location_name": loc_str,
            "source_url": url,
            "source_type": "scraper_web",
        })

    return events


def parse_fit_vut(html: str, base_url: str = "https://www.fit.vut.cz/fit/calendar/") -> List[Dict[str, Any]]:
    """
    Parses events from fit.vut.cz/fit/calendar/
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year
    now = datetime.datetime.now()

    for item in soup.select("li.c-events__item, .c-events__item"):
        link_el = item.find("a")
        if not link_el:
            continue

        url = urljoin(base_url, link_el.get("href", ""))
        title_el = item.find(["h2", "h3"], class_=lambda c: c and "title" in c)
        if not title_el:
            title_el = item.find(["h2", "h3", "h4"])
        if not title_el:
            continue
        title = title_el.get_text(strip=True)

        # Cover image extraction
        img_el = item.select_one(".b-term__img img, img")
        cover_image = None
        if img_el and img_el.get("src"):
            cover_image = urljoin(base_url, img_el.get("src"))

        # Date parsing: check <time datetime="..."> first
        starts_at = None
        ends_at = None
        time_el = item.find("time")
        if time_el and time_el.get("datetime"):
            dt_raw = time_el.get("datetime")
            try:
                dt_parsed = datetime.datetime.fromisoformat(dt_raw)
                starts_at = dt_parsed.strftime("%Y-%m-%d %H:%M:%S")
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
                starts_at = f"{year:04d}-{month:02d}-{day:02d} 14:00:00"
                if end_day and 1 <= end_day <= 31:
                    ends_at = f"{year:04d}-{month:02d}-{end_day:02d} 18:00:00"
            else:
                starts_at = f"{current_year:04d}-11-20 14:00:00"

        # Skip past events older than 7 days
        try:
            event_dt = datetime.datetime.strptime(starts_at[:10], "%Y-%m-%d")
            if event_dt < (now - datetime.timedelta(days=7)):
                continue
        except Exception:
            pass

        desc = f"Událost Fakulty informačních technologií VUT v Brně: {title}."

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": ends_at,
            "is_all_day": False,
            "cover_image": cover_image,
            "location_name": "FIT VUT, Božetěchova 1/2, Brno – Královo Pole",
            "source_url": url,
            "source_type": "scraper_web",
        })

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

        # Prefer Brno events or all university presentations
        starts_at = f"{year:04d}-{month:02d}-{day:02d} 09:00:00"
        url = base_url + f"#{abs(hash(title + starts_at))}"

        desc = f"Událost Univerzity obrany: {title}."
        loc = "Areál Univerzity obrany (Kasárna Černá Pole / Šumavská), Brno" if "Brno" in title or "FVT" in title or "FVL" in title else "Univerzita obrany"

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


def parse_muni(html: str, base_url: str = "https://www.muni.cz/kalendar") -> List[Dict[str, Any]]:
    """
    Parses events from muni.cz/kalendar (.box-event)
    Filters out non-event items (CŽV courses, administrative procedures, secondary school events, NLZP courses).
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year

    exclude_patterns = [
        r"cžv", r"celoživotn", r"vzdělávací kurz",
        r"pro studenty středních škol", r"pro žáky", r"pro učitele", r"žáků zuš",
        r"pro nlzp", r"nelékařsk", r"řízení ke jmenování",
        r"habilitačn", r"inaugurač", r"akademický senát", r"vědecká rada",
        r"disertačn", r"rigorózní", r"přijímací řízení", r"zápis do studia",
        r"simulační kurz akutních stavů", r"kontinuální vzdělávání lékařů",
    ]

    for item in soup.select(".box-event"):
        # Remove any decorative badge like "TOP AKCE" or ribbons
        for badge in item.select(".box-event__badge, .badge, .ribbon"):
            badge.decompose()

        full_card_text = item.get_text(" ", strip=True).lower()
        if any(re.search(pat, full_card_text) for pat in exclude_patterns):
            continue

        link_el = item.find("a")
        url = urljoin(base_url, link_el.get("href", "")) if link_el else base_url
        
        title_el = item.select_one(".box-event__title")
        if not title_el:
            title_el = item.find(["h4", "h3", "h2"])
        if not title_el:
            continue

        # Clone and decompose icons / sub-elements to avoid dirty title
        t_copy = BeautifulSoup(str(title_el), "lxml")
        for sub in t_copy.find_all(["span", "small", "i", "div"]):
            sub.decompose()
        title = t_copy.get_text(" ", strip=True)

        # Strip prefixes
        title = re.sub(r"^(top akce|akce:?)\s*", "", title, flags=re.IGNORECASE).strip()
        # Enforce clean max length
        if len(title) > 90:
            title = title[:87].rsplit(" ", 1)[0] + "..." if " " in title[:87] else title[:87] + "..."

        if len(title) < 4:
            continue

        # Extract image if present
        img_el = item.select_one(".box-event__img img, img")
        cover_image = None
        if img_el and img_el.get("src"):
            cover_image = urljoin(base_url, img_el.get("src"))

        # Parse date and times
        date_el = item.select_one(".box-event__date")
        date_str = date_el.get_text(" ", strip=True) if date_el else ""

        times = re.findall(r"(\d{1,2}:\d{2})", date_str)
        start_time = f"{int(times[0].split(':')[0]):02d}:{times[0].split(':')[1]}:00" if times else "17:00:00"
        end_time = f"{int(times[1].split(':')[0]):02d}:{times[1].split(':')[1]}:00" if len(times) > 1 else None

        # Check for range: "11. – 25. 11."
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
        ends_at = None
        if end_day and end_month:
            end_year = year
            if end_month < start_month:
                end_year += 1
            e_time = end_time if end_time else "23:59:59"
            ends_at = f"{end_year:04d}-{end_month:02d}-{end_day:02d} {e_time}"
        elif end_time:
            ends_at = f"{year:04d}-{start_month:02d}-{start_day:02d} {end_time}"

        # Teaser description from content
        content_el = item.select_one(".box-event__content")
        desc = ""
        if content_el:
            c_copy = BeautifulSoup(str(content_el), "lxml")
            for rm in c_copy.select(".box-event__title, .box-event__date, .box-event__tags, .meta, span, h4"):
                rm.decompose()
            desc = c_copy.get_text(" ", strip=True)

        desc = re.sub(r"\btop\s*akce:?\s*", "", desc, flags=re.IGNORECASE).strip()

        if not desc:
            desc = f"Událost Masarykovy univerzity: {title} ({date_str})."

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": ends_at,
            "is_all_day": False,
            "cover_image": cover_image,
            "location_name": "Masarykova univerzita, Brno",
            "source_url": url,
            "source_type": "scraper_web",
        })

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
