#!/usr/bin/env python3
import argparse
import json
import sys
import os

# Ensure both scrapers directory and project root are in sys.path
scrapers_dir = os.path.dirname(os.path.abspath(__file__))
project_root = os.path.dirname(scrapers_dir)
if scrapers_dir not in sys.path:
    sys.path.insert(0, scrapers_dir)
if project_root not in sys.path:
    sys.path.insert(0, project_root)

import httpx

import shutil

from parsers.ical_parser import parse_ical
from parsers.rss_parser import parse_rss
from parsers.instagram_parser import parse_instagram
from parsers.facebook_parser import parse_facebook
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
            "web_instagram", "instagram",
            "web_facebook", "facebook",
        ],
        help="Type of scraper parser to execute",
    )
    parser.add_argument("--university", default="", help="Default university tag (MUNI, VUT, MENDELU, UNOB, VETUNI)")
    parser.add_argument("--limit", type=int, default=30, help="Maximum number of events to extract")
    parser.add_argument("--dry-run", action="store_true", help="Run in dry-run mode without external HTTP requests or AI API calls")
    parser.add_argument(
        "--engine",
        choices=["auto", "llm", "deterministic"],
        default="deterministic",
        help="Extraction engine: deterministic (regex/DOM) or llm (Antigravity batch extractor with fallback)",
    )
    parser.add_argument(
        "--force-all",
        action="store_true",
        help="Force re-extraction of all cards ignoring SHA-256 hash cache",
    )

    args = parser.parse_args()

    try:
        stype = args.type.lower()

        if args.dry_run:
            fixtures_dir = os.path.join(scrapers_dir, "fixtures")
            if stype in ["web_instagram", "instagram"]:
                from parsers.instagram_parser import extract_event_from_caption, sanitize_extracted_event_date
                sample_file = os.path.join(fixtures_dir, "sample_social_posts.json")
                events = []
                if os.path.exists(sample_file):
                    with open(sample_file, "r", encoding="utf-8") as f:
                        data = json.load(f)
                    for item in data.get("instagram", []):
                        if item.get("should_ignore"):
                            continue
                        ev = extract_event_from_caption(item["caption"], base_url=f"https://instagram.com/{args.url}")
                        if ev:
                            ev = sanitize_extracted_event_date(ev, item["caption"])
                        if ev:
                            events.append(ev)
            elif stype in ["web_facebook", "facebook"]:
                from parsers.facebook_parser import extract_event_from_text
                sample_file = os.path.join(fixtures_dir, "sample_social_posts.json")
                events = []
                if os.path.exists(sample_file):
                    with open(sample_file, "r", encoding="utf-8") as f:
                        data = json.load(f)
                    for item in data.get("facebook", []):
                        ev = extract_event_from_text(item["text"], base_url=f"https://facebook.com/{args.url}", university=args.university)
                        if ev:
                            events.append(ev)
            else:
                fixture_map = {
                    "web_ical": "sample_student_events.ics",
                    "ical": "sample_student_events.ics",
                    "web_rss": "sample_feed.xml",
                    "rss": "sample_feed.xml",
                    "web_mendelu": "sample_mendelu.html",
                    "mendelu": "sample_mendelu.html",
                    "web_fit_vut": "sample_fit_vut.html",
                    "fit_vut": "sample_fit_vut.html",
                    "web_unob": "sample_unob.html",
                    "unob": "sample_unob.html",
                    "web_muni": "sample_muni.html",
                    "muni": "sample_muni.html",
                }
                fixture_name = fixture_map.get(stype, "sample_mendelu.html")
                fixture_path = os.path.join(fixtures_dir, fixture_name)

                if args.url.startswith("file://") or os.path.exists(args.url):
                    raw_content = fetch_content(args.url)
                else:
                    raw_content = fetch_content(fixture_path)

                if stype in ["web_ical", "ical"]:
                    events = parse_ical(raw_content, source_url=args.url)
                elif stype in ["web_rss", "rss"]:
                    events = parse_rss(raw_content, source_url=args.url)
                else:
                    text_content = raw_content.decode("utf-8", errors="replace") if isinstance(raw_content, bytes) else raw_content
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
        elif stype in ["web_instagram", "instagram"]:
            events = parse_instagram(args.url, limit=args.limit)
        elif stype in ["web_facebook", "facebook"]:
            events = parse_facebook(args.url, university=args.university, limit=args.limit)
        else:
            raw_content = fetch_content(args.url)
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

                events = []
                use_llm = (args.engine in ["llm", "auto"]) and not args.dry_run and shutil.which("agy") is not None

                if use_llm and stype not in ["web_ical", "ical", "web_rss", "rss"]:
                    try:
                        from card_slicer import slice_raw_content
                        from ai_batch_extractor import extract_events_with_ai_pipeline

                        cards = slice_raw_content(text_content, stype, source_url=args.url)
                        if cards:
                            ai_res = extract_events_with_ai_pipeline(cards, university=args.university, force_all=args.force_all)
                            ai_events = ai_res.get("extracted_events", [])
                            if ai_events:
                                events = ai_events
                    except Exception as ai_err:
                        sys.stderr.write(f"Warning: LLM pipeline error ({ai_err}), falling back to deterministic parser.\n")

                if not events:
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
