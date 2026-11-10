import datetime
from typing import List, Dict, Any, Optional
import icalendar


def parse_ical(content: str or bytes, source_url: str = "") -> List[Dict[str, Any]]:
    """
    Parses an iCalendar (.ics) feed and returns a list of normalized event dictionaries.
    """
    if isinstance(content, str):
        content = content.encode("utf-8")

    calendar = icalendar.Calendar.from_ical(content)
    events = []

    for component in calendar.walk():
        if component.name != "VEVENT":
            continue

        summary = component.get("SUMMARY")
        if not summary:
            continue
        title = str(summary).strip()

        # Dates
        dtstart = component.get("DTSTART")
        if not dtstart:
            continue

        start_val = dtstart.dt
        if isinstance(start_val, datetime.datetime):
            starts_at_str = start_val.strftime("%Y-%m-%d %H:%M:%S")
            is_all_day = False
        elif isinstance(start_val, datetime.date):
            starts_at_str = f"{start_val.strftime('%Y-%m-%d')} 09:00:00"
            is_all_day = True
        else:
            continue

        ends_at_str = None
        dtend = component.get("DTEND")
        if dtend:
            end_val = dtend.dt
            if isinstance(end_val, datetime.datetime):
                ends_at_str = end_val.strftime("%Y-%m-%d %H:%M:%S")
            elif isinstance(end_val, datetime.date):
                ends_at_str = f"{end_val.strftime('%Y-%m-%d')} 23:59:59"

        # Description
        desc_raw = component.get("DESCRIPTION", "")
        description = str(desc_raw).strip() if desc_raw else ""
        short_description = description[:200] + "..." if len(description) > 200 else description

        # Location
        location_raw = component.get("LOCATION", "")
        location_name = str(location_raw).strip() if location_raw else None

        # URL
        url_raw = component.get("URL", "")
        item_url = str(url_raw).strip() if url_raw else source_url
        if not item_url:
            uid = str(component.get("UID", ""))
            item_url = f"{source_url}#{uid}" if uid else f"{source_url}#{abs(hash(title + starts_at_str))}"

        events.append({
            "title": title,
            "short_description": short_description,
            "description": description,
            "starts_at": starts_at_str,
            "ends_at": ends_at_str,
            "is_all_day": is_all_day,
            "location_name": location_name,
            "source_url": item_url,
            "source_type": "scraper_web",
        })

    return events
