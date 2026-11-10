#!/usr/bin/env python3
import argparse
import json
import sys
import os
import httpx

from parsers.ical_parser import parse_ical
from parsers.rss_parser import parse_rss
from parsers.web_parsers import (
    parse_mendelu,
    parse_fit_vut,
    parse_unob,
    parse_muni,
    parse_generic_html,
)


def fetch_content(url: str) -> str or bytes:
    if url.startswith("file://"):
        local_path = url[len("file://"):]
        with open(local_path, "rb") as f:
            return f.read()
    elif os.path.exists(url):
        with open(url, "rb") as f:
            return f.read()
    else:
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 BrnoEventsScraper/1.0",
            "Accept": "text/html,application/xhtml+xml,application/xml,text/calendar;q=0.9,*/*;q=0.8",
            "Accept-Language": "cs,en;q=0.9",
        }
        with httpx.Client(headers=headers, timeout=20.0, follow_redirects=True) as client:
            resp = client.get(url)
            resp.raise_for_status()
            return resp.content


def main():
    parser = argparse.ArgumentParser(description="Brno Student Events Scraper Engine")
    parser.add_argument("--url", required=True, help="Target URL or local file path to scrape")
    parser.add_argument(
        "--type",
        required=True,
        choices=[
            "web_ical", "ical",
            "web_rss", "rss",
            "web_mendelu", "mendelu",
            "web_fit_vut", "fit_vut",
            "web_unob", "unob",
            "web_muni", "muni",
            "web_generic", "generic",
        ],
        help="Type of scraper parser to execute",
    )
    parser.add_argument("--university", default="", help="Default university tag (MUNI, VUT, MENDELU, UNOB, VETUNI)")
    parser.add_argument("--limit", type=int, default=30, help="Maximum number of events to extract")

    args = parser.parse_args()

    try:
        raw_content = fetch_content(args.url)
        stype = args.type.lower()

        if stype in ["web_ical", "ical"]:
            events = parse_ical(raw_content, source_url=args.url)
        elif stype in ["web_rss", "rss"]:
            events = parse_rss(raw_content, source_url=args.url)
        else:
            # Decode HTML string
            if isinstance(raw_content, bytes):
                text_content = raw_content.decode("utf-8", errors="replace")
            else:
                text_content = raw_content

            if stype in ["web_mendelu", "mendelu"]:
                events = parse_mendelu(text_content, base_url=args.url)
            elif stype in ["web_fit_vut", "fit_vut"]:
                events = parse_fit_vut(text_content, base_url=args.url)
            elif stype in ["web_unob", "unob"]:
                events = parse_unob(text_content, base_url=args.url)
            elif stype in ["web_muni", "muni"]:
                events = parse_muni(text_content, base_url=args.url)
            else:
                events = parse_generic_html(text_content, base_url=args.url)

        # Apply university tag if provided
        if args.university:
            for ev in events:
                ev["university"] = args.university

        if args.limit and len(events) > args.limit:
            events = events[:args.limit]

        output = {
            "status": "success",
            "count": len(events),
            "events": events,
        }
        print(json.dumps(output, ensure_ascii=False, indent=2))
        return 0

    except Exception as e:
        output = {
            "status": "error",
            "error": str(e),
            "events": [],
        }
        print(json.dumps(output, ensure_ascii=False, indent=2), file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
