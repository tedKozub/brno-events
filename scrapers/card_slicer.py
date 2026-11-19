#!/usr/bin/env python3
"""
Card Slicer & SHA-256 Hasher for BrnoEvents Harvester Pipeline.
Takes raw HTML/JSON/ICS content, strips noise, slices into event cards,
and detects new/modified cards using content hashing.
"""

import os
import sys
import json
import hashlib
from typing import List, Dict, Any, Optional
from bs4 import BeautifulSoup


def get_hash_cache_path(base_dir: Optional[str] = None) -> str:
    if not base_dir:
        base_dir = os.path.join(os.path.dirname(os.path.dirname(os.path.abspath(__file__))), "storage", "app", "scraping_raw")
    os.makedirs(base_dir, exist_ok=True)
    return os.path.join(base_dir, ".card_hashes.json")


def load_hash_cache(cache_path: str) -> Dict[str, str]:
    if os.path.exists(cache_path):
        try:
            with open(cache_path, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception:
            return {}
    return {}


def save_hash_cache(cache_path: str, cache: Dict[str, str]) -> None:
    try:
        with open(cache_path, "w", encoding="utf-8") as f:
            json.dump(cache, f, indent=2, ensure_ascii=False)
    except Exception as e:
        sys.stderr.write(f"Warning: Failed to save hash cache: {e}\n")


def clean_html_card(element) -> str:
    """Strips useless tags and extracts clean normalized text from an HTML card."""
    for bad in element(["script", "style", "svg", "noscript", "iframe"]):
        bad.decompose()
    text = element.get_text(separator=" ", strip=True)
    # Collapse multiple whitespaces
    return " ".join(text.split())


def slice_mendelu(soup: BeautifulSoup, base_url: str = "https://mendelu.cz") -> List[Dict[str, Any]]:
    cards = []
    seen_urls = set()
    for item in soup.select("li.event-card__item, .event-card"):
        card = item if item.name == "a" else (item.find("a", class_="event-card") or item.find("a") or item)
        if not card:
            continue

        link = card if card.name == "a" else card.find("a", href=True)
        url = link.get("href", "") if link else ""
        if url and not url.startswith("http"):
            url = base_url.rstrip("/") + ("/" + url.lstrip("/"))

        if url in seen_urls:
            continue
        if url:
            seen_urls.add(url)

        cleaned_text = clean_html_card(card)
        if len(cleaned_text) < 20:
            continue

        cards.append({
            "card_id": url or hashlib.md5(cleaned_text.encode("utf-8")).hexdigest(),
            "source_url": url,
            "raw_text": cleaned_text,
        })
    return cards


def slice_fit_vut(soup: BeautifulSoup, base_url: str = "https://www.fit.vut.cz") -> List[Dict[str, Any]]:
    cards = []
    seen_urls = set()
    for item in soup.select("li.c-events__item, .c-events__item, .c-events-list__item"):
        link_tag = item.find("a", href=True)
        url = link_tag["href"] if link_tag else ""
        if url and not url.startswith("http"):
            url = base_url.rstrip("/") + ("/" + url.lstrip("/"))

        if url in seen_urls:
            continue
        if url:
            seen_urls.add(url)

        cleaned_text = clean_html_card(item)
        if len(cleaned_text) < 20:
            continue

        cards.append({
            "card_id": url or hashlib.md5(cleaned_text.encode("utf-8")).hexdigest(),
            "source_url": url,
            "raw_text": cleaned_text,
        })
    return cards


def slice_muni(soup: BeautifulSoup, base_url: str = "https://www.muni.cz") -> List[Dict[str, Any]]:
    cards = []
    seen = set()
    # MUNI calendar
    for item in soup.find_all(["article", "div", "li"], class_=lambda c: c and ("calendar" in c or "event" in c)):
        link_tag = item.find("a", href=True)
        url = link_tag["href"] if link_tag else ""
        if url and not url.startswith("http"):
            url = base_url.rstrip("/") + ("/" + url.lstrip("/"))

        if url in seen:
            continue
        if url:
            seen.add(url)

        cleaned_text = clean_html_card(item)
        if len(cleaned_text) < 25:
            continue

        cards.append({
            "card_id": url or hashlib.md5(cleaned_text.encode("utf-8")).hexdigest(),
            "source_url": url,
            "raw_text": cleaned_text,
        })
    return cards


def slice_generic_html(soup: BeautifulSoup, base_url: str = "") -> List[Dict[str, Any]]:
    cards = []
    seen = set()
    candidates = soup.find_all(["article", "li", "div"], class_=lambda c: c and any(k in str(c).lower() for k in ["event", "akce", "card", "item", "calendar"]))
    for item in candidates:
        link_tag = item.find("a", href=True)
        url = link_tag["href"] if link_tag else ""
        if url and not url.startswith("http") and base_url:
            url = base_url.rstrip("/") + ("/" + url.lstrip("/"))

        if url and url in seen:
            continue
        if url:
            seen.add(url)

        cleaned_text = clean_html_card(item)
        if len(cleaned_text) < 30 or len(cleaned_text) > 4000:
            continue

        cards.append({
            "card_id": url or hashlib.md5(cleaned_text.encode("utf-8")).hexdigest(),
            "source_url": url,
            "raw_text": cleaned_text,
        })
    return cards


def slice_raw_content(
    content: str,
    source_type: str,
    source_url: str = "",
    cache_path: Optional[str] = None
) -> List[Dict[str, Any]]:
    """
    Slices raw content into cards, calculates SHA-256 for each card,
    and marks whether the card is new or modified compared to the hash cache.
    """
    stype = source_type.lower()
    soup = None

    if stype.startswith("web_") or "html" in stype:
        soup = BeautifulSoup(content, "html.parser")

    raw_cards: List[Dict[str, Any]] = []

    if "mendelu" in stype and soup:
        raw_cards = slice_mendelu(soup, source_url)
    elif "fit_vut" in stype and soup:
        raw_cards = slice_fit_vut(soup, source_url)
    elif "muni" in stype and soup:
        raw_cards = slice_muni(soup, source_url)
    elif soup:
        raw_cards = slice_generic_html(soup, source_url)
    elif "json" in stype or "instagram" in stype or "facebook" in stype:
        try:
            data = json.loads(content)
            items = data if isinstance(data, list) else data.get("items", data.get("posts", [data]))
            for idx, post in enumerate(items):
                text = post.get("caption") or post.get("text") or post.get("message") or json.dumps(post, ensure_ascii=False)
                url = post.get("url") or post.get("permalink") or f"{source_url}#{idx}"
                raw_cards.append({
                    "card_id": url,
                    "source_url": url,
                    "raw_text": text,
                })
        except Exception:
            pass

    if not cache_path:
        cache_path = get_hash_cache_path()
    cache = load_hash_cache(cache_path)

    processed_cards = []
    for card in raw_cards:
        card_text = card["raw_text"]
        card_hash = hashlib.sha256(card_text.encode("utf-8")).hexdigest()
        card_id = card["card_id"]

        is_new_or_changed = cache.get(card_id) != card_hash

        processed_cards.append({
            "card_id": card_id,
            "source_url": card.get("source_url", ""),
            "raw_text": card_text,
            "sha256": card_hash,
            "is_new_or_changed": is_new_or_changed,
        })

    return processed_cards
