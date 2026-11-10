import datetime
import time
from typing import List, Dict, Any
import feedparser
from bs4 import BeautifulSoup


def parse_rss(content: str or bytes, source_url: str = "") -> List[Dict[str, Any]]:
    """
    Parses an RSS or Atom feed and returns a list of normalized event dictionaries.
    """
    feed = feedparser.parse(content)
    events = []

    for entry in feed.entries:
        title = entry.get("title", "").strip()
        if not title:
            continue

        link = entry.get("link", "").strip() or source_url

        # Description / Summary (clean HTML tags)
        summary_raw = entry.get("summary", "") or entry.get("description", "")
        clean_desc = ""
        if summary_raw:
            soup = BeautifulSoup(summary_raw, "html.parser")
            clean_desc = soup.get_text(separator=" ", strip=True)

        short_description = clean_desc[:200] + "..." if len(clean_desc) > 200 else clean_desc

        # Publication or Event date
        date_struct = entry.get("published_parsed") or entry.get("updated_parsed")
        if date_struct:
            dt = datetime.datetime.fromtimestamp(time.mktime(date_struct))
            starts_at_str = dt.strftime("%Y-%m-%d %H:%M:%S")
        else:
            # Fallback to tomorrow if no date is provided
            tomorrow = datetime.datetime.now() + datetime.timedelta(days=1)
            starts_at_str = tomorrow.strftime("%Y-%m-%d 18:00:00")

        events.append({
            "title": title,
            "short_description": short_description,
            "description": clean_desc,
            "starts_at": starts_at_str,
            "ends_at": None,
            "is_all_day": False,
            "location_name": None,
            "source_url": link,
            "source_type": "scraper_web",
        })

    return events
