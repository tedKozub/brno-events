import asyncio
import base64
import hashlib
import json
import os
import re
import urllib.request
from datetime import datetime, timedelta
from typing import Any, Dict, List, Optional, Tuple
import httpx
from playwright.async_api import async_playwright

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
}

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
    m_num = re.search(r"\b(\d{1,2})\.\s*(\d{1,2})\.(?:\s*(\d{4}))?\b", text)
    if m_num:
        day = int(m_num.group(1))
        month = int(m_num.group(2))
        year = int(m_num.group(3)) if m_num.group(3) else default_year
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

    # 2. If NO explicit date exists, do NOT guess or invent upcoming dates (e.g. beerpong "středy").
    # Return None so the event without a specific calendar date is ignored.
    return None


def clean_emoji(text: str) -> str:
    """Removes emojis from text per BrnoAkce guidelines."""
    emoji_pattern = re.compile(
        "["
        "\U0001F600-\U0001F64F"  # emoticons
        "\U0001F300-\U0001F5FF"  # symbols & pictographs
        "\U0001F680-\U0001F6FF"  # transport & map symbols
        "\U0001F1E0-\U0001F1FF"  # flags (iOS)
        "\U00002702-\U000027B0"
        "\U000024C2-\U0001F251"
        "\U0001F900-\U0001F9FF"  # supplemental symbols
        "\U0001FA70-\U0001FAFF"  # symbols and pictographs extended
        "]+",
        flags=re.UNICODE,
    )
    return emoji_pattern.sub("", text).strip()


def get_gemini_api_key() -> Optional[str]:
    """Retrieves GEMINI_API_KEY from environment or .env file."""
    key = os.environ.get("GEMINI_API_KEY")
    if key:
        return key

    env_path = "/root/brno-events/.env"
    if os.path.exists(env_path):
        with open(env_path, "r", encoding="utf-8") as f:
            for line in f:
                line = line.strip()
                if line.startswith("GEMINI_API_KEY="):
                    val = line.split("=", 1)[1].strip().strip('"').strip("'")
                    if val:
                        return val
    return None


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


def extract_with_openai_vision(image_path: str, caption: str, api_key: str) -> Optional[Dict[str, Any]]:
    """Uses OpenAI gpt-4o-mini Vision to extract structured event details from poster image + caption."""
    try:
        with open(image_path, "rb") as f:
            img_b64 = base64.b64encode(f.read()).decode("utf-8")

        now = datetime.now()
        today_cz = CZECH_WEEKDAY_NAMES.get(now.weekday(), "")

        prompt = f"""Jsi expert na brněnský studentský život. Analyzuj přiložený grafický plakát a text příspěvku z Instagramu.
Dnešní datum je: {now.strftime('%Y-%m-%d')} ({today_cz}).

Extrahuj informace o události a vrať POUZE validní JSON ve formátu:
{{
  "title": "Přesný název akce z plakátu (bez emoji)",
  "starts_at": "YYYY-MM-DD HH:MM:SS",
  "ends_at": null nebo "YYYY-MM-DD HH:MM:SS",
  "is_all_day": false,
  "location_name": "Přesný název klubu, kavárny, fakulty nebo sálu v Brně",
  "price_type": "free" nebo "paid" nebo "voluntary",
  "price_amount": 150 nebo null,
  "category_slug": "party-kluby" nebo "prednasky-workshopy" nebo "sport" nebo "kultura-umeni" nebo "kariera-networking",
  "short_description": "Stručné shrnutí akce pro studenty na 1-2 věty bez emoji",
  "description": "Kompletní popis a program bez emoji"
}}

STRIKTNÍ PRAVIDLA PRO DATUM (starts_at):
1. AKCI EXTRAHUJ POUZE V PŘÍPADĚ, ŽE JE NA PLAKÁTU NEBO V POPISKU UVEDENO KONKRÉTNÍ KALENDÁŘNÍ DATUM (např. 10.11., 18.11., 25. listopadu, 4.3.2027). Použij přesně toto datum z plakátu či popisku bez jakéhokoliv domýšlení či posouvání v čase!
2. POKUD JE NA PLAKÁTU NEBO V TEXTU UVEDEN POUZE DEN V TÝDNU NEBO OPAKOVÁNÍ (např. 'středy', 'každé úterý', 'čtvrtek') BEZ KONKRÉTNÍHO KALENDÁŘNÍHO DATA, VRAŤ "starts_at": null, PROTOŽE AKCE BEZ KONKRÉTNÍHO KALENDÁŘNÍHO DATA IGNORUJEME.
3. Čas vyber dle plakátu a textu (např. 19:00 -> 19:00:00). Pokud čas chybí, použij 18:00:00.

Popisek z Instagramu:
{caption}
"""
        url = "https://api.openai.com/v1/chat/completions"
        headers = {
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json",
        }
        payload = {
            "model": "gpt-4o-mini",
            "messages": [
                {
                    "role": "system",
                    "content": "Jsi asistent pro extrakci studentských akcí v Brně z plakátů do strukturovaného JSONu bez emoji. Striktně se drž explicitně uvedených dat na plakátu a v popisku a akce bez konkrétního kalendářního data ignoruj s starts_at: null.",
                },
                {
                    "role": "user",
                    "content": [
                        {"type": "text", "text": prompt},
                        {
                            "type": "image_url",
                            "image_url": {
                                "url": f"data:image/jpeg;base64,{img_b64}",
                                "detail": "high",
                            },
                        },
                    ],
                },
            ],
            "response_format": {"type": "json_object"},
            "max_tokens": 1000,
        }

        with httpx.Client(timeout=25.0) as client:
            resp = client.post(url, json=payload, headers=headers)
            if resp.status_code == 200:
                data = resp.json()
                content = data["choices"][0]["message"]["content"]
                extracted = json.loads(content)
                if not extracted or not extracted.get("starts_at"):
                    return None
                extracted["title"] = clean_emoji(extracted.get("title", ""))
                extracted["location_name"] = clean_emoji(extracted.get("location_name", "Brno"))
                extracted["short_description"] = clean_emoji(extracted.get("short_description", ""))
                if "description" in extracted:
                    extracted["description"] = clean_emoji(extracted["description"])
                return sanitize_extracted_event_date(extracted, caption, now=now)
            else:
                print(f"OpenAI error {resp.status_code}: {resp.text}", flush=True)
    except Exception as e:
        print(f"OpenAI vision extraction failed: {e}", flush=True)

    return None


def extract_with_gemini_vision(image_path: str, caption: str, api_key: str) -> Optional[Dict[str, Any]]:
    """Uses Gemini 2.0 / 1.5 Flash Vision to extract structured event details from poster image + caption."""
    try:
        with open(image_path, "rb") as f:
            img_b64 = base64.b64encode(f.read()).decode("utf-8")

        now = datetime.now()
        today_cz = CZECH_WEEKDAY_NAMES.get(now.weekday(), "")

        prompt = f"""Jsi expert na brněnský studentský život. Analyzuj přiložený grafický plakát a text příspěvku z Instagramu.
Dnešní datum je: {now.strftime('%Y-%m-%d')} ({today_cz}).

Extrahuj informace o události a vrať POUZE validní JSON ve formátu:
{{
  "title": "Přesný název akce z plakátu (bez emoji)",
  "starts_at": "YYYY-MM-DD HH:MM:SS",
  "ends_at": null nebo "YYYY-MM-DD HH:MM:SS",
  "is_all_day": false,
  "location_name": "Přesný název klubu, kavárny, fakulty nebo sálu v Brně",
  "price_type": "free" nebo "paid" nebo "voluntary",
  "price_amount": 150 nebo null,
  "category_slug": "party-kluby" nebo "prednasky-workshopy" nebo "sport" nebo "kultura-umeni" nebo "kariera-networking",
  "short_description": "Stručné shrnutí akce pro studenty na 1-2 věty bez emoji",
  "description": "Kompletní popis a program"
}}

STRIKTNÍ PRAVIDLA PRO DATUM (starts_at):
1. AKCI EXTRAHUJ POUZE V PŘÍPADĚ, ŽE JE NA PLAKÁTU NEBO V POPISKU UVEDENO KONKRÉTNÍ KALENDÁŘNÍ DATUM (např. 10.11., 18.11., 25. listopadu, 4.3.2027). Použij přesně toto datum z plakátu či popisku bez jakéhokoliv domýšlení či posouvání v čase!
2. POKUD JE NA PLAKÁTU NEBO V TEXTU UVEDEN POUZE DEN V TÝDNU NEBO OPAKOVÁNÍ (např. 'středy', 'každé úterý', 'čtvrtek') BEZ KONKRÉTNÍHO KALENDÁŘNÍHO DATA, VRAŤ "starts_at": null, PROTOŽE AKCE BEZ KONKRÉTNÍHO KALENDÁŘNÍHO DATA IGNORUJEME.
3. Čas vyber dle plakátu a textu (např. 19:00 -> 19:00:00). Pokud čas chybí, použij 18:00:00.

Popisek z Instagramu:
{caption}
"""
        url = f"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key={api_key}"
        payload = {
            "contents": [
                {
                    "parts": [
                        {"text": prompt},
                        {
                            "inline_data": {
                                "mime_type": "image/jpeg",
                                "data": img_b64,
                            }
                        },
                    ]
                }
            ],
            "generationConfig": {
                "response_mime_type": "application/json",
            },
        }

        with httpx.Client(timeout=25.0) as client:
            resp = client.post(url, json=payload)
            if resp.status_code == 200:
                data = resp.json()
                text = data["candidates"][0]["content"]["parts"][0]["text"]
                extracted = json.loads(text)
                if not extracted or not extracted.get("starts_at"):
                    return None
                extracted["title"] = clean_emoji(extracted.get("title", ""))
                extracted["location_name"] = clean_emoji(extracted.get("location_name", "Brno"))
                extracted["short_description"] = clean_emoji(extracted.get("short_description", ""))
                return sanitize_extracted_event_date(extracted, caption, now=now)
    except Exception as e:
        print(f"Gemini vision extraction failed, falling back to NLP: {e}", flush=True)

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

    venue = "Brno"
    venue_match = re.search(r"(?:Kde|Místo|📍)\s*:?\s*([^\n]+)", cleaned, re.IGNORECASE)
    cleaned_lower = cleaned.lower()
    if venue_match:
        venue = clean_emoji(venue_match.group(1).strip())
        venue = re.sub(r"^(?:ve?|at|in|do)\s+", "", venue, flags=re.IGNORECASE).strip()
    elif "koma lounge" in cleaned_lower:
        venue = "Koma Lounge & Bar, Kounicova 42, Brno"
    elif "skautský institut" in cleaned_lower or "skautskem institutu" in cleaned_lower:
        venue = "Skautský institut v Brně, Moravské náměstí 12, Brno"
    elif "babylon" in cleaned_lower:
        venue = "KC Babylon, Kounicova 20, Brno"
    elif "morgal" in cleaned_lower:
        venue = "Café Morgal, Moravské náměstí 1a, Brno"
    elif "kabinet múz" in cleaned_lower or "kabinet muz" in cleaned_lower:
        venue = "Kabinet MÚZ, Sukova 4, Brno"
    elif "music lab" in cleaned_lower:
        venue = "Music Lab, Opletalova 1, Brno"
    elif "trojk" in cleaned_lower:
        venue = "Kavárna Trojka, Dominikánská 9, Brno"
    elif "fleda" in cleaned_lower or "fléda" in cleaned_lower:
        venue = "Fléda Club, Štefánikova 24, Brno"
    elif "artbar" in cleaned_lower or "druhý pád" in cleaned_lower or "druhy pad" in cleaned_lower:
        venue = "ArtBar Druhý Pád, Štefánikova 1, Brno"
    elif "perpetuum" in cleaned_lower:
        venue = "Perpetuum Club, Rooseveltova 9, Brno"
    elif "šelepk" in cleaned_lower or "selepk" in cleaned_lower:
        venue = "Klub Šelepka, Šelepova 1, Brno"
    elif "impact hub" in cleaned_lower:
        venue = "Impact Hub Brno, Cyrilská 7, Brno"
    elif "otevřená zahrada" in cleaned_lower or "otevrena zahrada" in cleaned_lower:
        venue = "Otevřená zahrada, Údolní 33, Brno"
    else:
        # Hledání adresy ve tvaru Ulice Číslo, Brno
        addr_match = re.search(r"\b([A-ZÁ-Ž][a-zčďéěíňóřšťúůýž]+(?:\s+[a-zčďéěíňóřšťúůýž]+)?\s+\d+(?:[a-zA-Z]|\/\d+)?)(?:,\s*Brno)?\b", cleaned)
        if addr_match:
            venue = f"{addr_match.group(1).strip()}, Brno"

    price_type = "free"
    price_amount = None
    price_match = re.search(r"(\d+)\s*(?:Kč|czk)", cleaned, re.IGNORECASE)
    if price_match:
        price_type = "paid"
        price_amount = int(price_match.group(1))
    elif any(k in cleaned.lower() for k in ["vstupné", "startovné", "vstup"]):
        if "zdarma" in cleaned.lower() or "free" in cleaned.lower():
            price_type = "free"
        else:
            price_type = "paid"

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
    gemini_key = get_gemini_api_key()
    openai_key = get_openai_api_key()

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

                # Extract event: Multimodal Vision (OpenAI or Gemini) if key is available, else NLP fallback
                event = None
                if openai_key and os.path.exists(dest_path):
                    event = extract_with_openai_vision(dest_path, alt, openai_key)
                elif gemini_key and os.path.exists(dest_path):
                    event = extract_with_gemini_vision(dest_path, alt, gemini_key)

                if not event:
                    event = extract_event_from_caption(alt, base_url=profile_url)

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

