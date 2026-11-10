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

        # Date parsing
        day_el = item.find(class_=lambda c: c and "day" in c)
        month_el = item.find(class_=lambda c: c and "month" in c)
        year_el = item.find(class_=lambda c: c and "year" in c)

        day_matches = re.findall(r"\d+", day_el.get_text()) if day_el else []
        day = int(day_matches[0]) if day_matches else None
        end_day = int(day_matches[1]) if len(day_matches) > 1 else None

        month_str = month_el.get_text(strip=True).lower() if month_el else ""
        month = CZECH_MONTHS.get(month_str[:3], datetime.datetime.now().month)
        year = int(re.sub(r"\D", "", year_el.get_text())) if year_el and re.sub(r"\D", "", year_el.get_text()) else current_year

        ends_at = None
        if day and month:
            starts_at = f"{year:04d}-{month:02d}-{day:02d} 14:00:00"
            if end_day and 1 <= end_day <= 31:
                ends_at = f"{year:04d}-{month:02d}-{end_day:02d} 18:00:00"
        else:
            starts_at = f"{current_year:04d}-11-20 14:00:00"

        desc = f"Událost Fakulty informačních technologií VUT v Brně: {title}."

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": None,
            "is_all_day": False,
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
    """
    soup = BeautifulSoup(html, "lxml")
    events = []
    current_year = datetime.datetime.now().year

    for item in soup.select(".box-event"):
        link_el = item.find("a")
        url = urljoin(base_url, link_el.get("href", "")) if link_el else base_url
        
        title_el = item.find(["h3", "h2", "span"], class_=lambda c: c and "title" in c)
        if not title_el and link_el:
            title_el = link_el
        if not title_el:
            continue
        title = title_el.get_text(strip=True)

        date_el = item.find(class_=lambda c: c and "date" in c)
        date_str = date_el.get_text(strip=True) if date_el else ""

        # Parse date like "28. 8. – 31. 1." or "15. 11."
        day, month = 15, 11
        d_match = re.search(r"(\d{1,2})\.\s*(\d{1,2})\.", date_str)
        if d_match:
            day = int(d_match.group(1))
            month = int(d_match.group(2))

        year = current_year
        if month < datetime.datetime.now().month - 1:
            year += 1

        starts_at = f"{year:04d}-{month:02d}-{day:02d} 17:00:00"
        desc = f"Akce Masarykovy univerzity: {title} ({date_str})."

        events.append({
            "title": title,
            "short_description": desc[:200],
            "description": desc,
            "starts_at": starts_at,
            "ends_at": None,
            "is_all_day": False,
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
