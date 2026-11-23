"""
Centralized parsing, date/time extraction, and location resolution utilities
shared across all BrnoAkce scrapers (Web, Facebook, Instagram, iCal, RSS).
"""

import re
from datetime import datetime
from typing import Any, Dict, List, Optional, Tuple

CZECH_MONTHS: Dict[str, int] = {
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

CZECH_DAY_MAP: Dict[str, int] = {
    'pondělí': 0, 'pondeli': 0, 'po': 0,
    'úterý': 1, 'utery': 1, 'út': 1, 'ut': 1,
    'středa': 2, 'streda': 2, 'středy': 2, 'stredy': 2, 'st': 2,
    'čtvrtek': 3, 'ctvrtek': 3, 'čtvrtky': 3, 'ctvrtky': 3, 'čt': 3, 'ct': 3,
    'pátek': 4, 'patek': 4, 'pátky': 4, 'patky': 4, 'pá': 4, 'pa': 4,
    'sobota': 5, 'soboty': 5, 'so': 5,
    'neděle': 6, 'nedele': 6, 'neděle': 6, 'ne': 6,
}

# Known student venues and campuses with canonical names and addresses
KNOWN_VENUES: List[Dict[str, Any]] = [
    {
        'keywords': ['maison'],
        'name': 'Maison Club & Lounge',
        'address': 'Běhounská 9, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['koma lounge', 'koma bar'],
        'name': 'Koma Lounge & Bar',
        'address': 'Kounicova 42, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['skautský institut', 'skautskem institutu', 'skautského institutu'],
        'name': 'Skautský institut v Brně',
        'address': 'Moravské náměstí 12, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['fleda', 'fléda'],
        'name': 'Fléda Club',
        'address': 'Štefánikova 24, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['artbar', 'druhý pád', 'druhy pad'],
        'name': 'ArtBar Druhý Pád',
        'address': 'Štefánikova 1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['babylon'],
        'name': 'KC Babylon',
        'address': 'Kounicova 20, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['morgal'],
        'name': 'Café Morgal',
        'address': 'Moravské náměstí 1a, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['kabinet múz', 'kabinet muz'],
        'name': 'Kabinet MÚZ',
        'address': 'Sukova 4, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['music lab'],
        'name': 'Music Lab',
        'address': 'Opletalova 1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['trojka', 'kavárna trojka', 'kavarna trojka'],
        'name': 'Kavárna Trojka',
        'address': 'Dominikánská 9, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['šelepka', 'selepka'],
        'name': 'Klub Šelepka',
        'address': 'Šelepova 1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['sono music club', 'sono centrum', 'sono'],
        'name': 'Sono Music Club',
        'address': 'Veveří 113, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['perpetuum'],
        'name': 'Perpetuum Club',
        'address': 'Rooseveltova 9, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['otevřená zahrada', 'otevrena zahrada'],
        'name': 'Otevřená zahrada',
        'address': 'Údolní 33, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['impact hub'],
        'name': 'Impact Hub Brno',
        'address': 'Cyrilská 7, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['scala', 'kino scala'],
        'name': 'Univerzitní kino Scala',
        'address': 'Moravské náměstí 3, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['nová radnice', 'nova radnice', 'sněmovní sál nové radnice', 'snemovni sal nove radnice'],
        'name': 'Nová radnice',
        'address': 'Dominikánské náměstí 1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['stará radnice', 'stara radnice', 'radnická', 'radnicka'],
        'name': 'Stará radnice',
        'address': 'Radnická 8, Brno',
        'city': 'Brno',
    },
    # University specific locations and detached campuses
    {
        'keywords': ['lednice', 'valtická', 'zf mendelu'],
        'name': 'ZF MENDELU',
        'address': 'Valtická 337, Lednice',
        'city': 'Lednice',
    },
    {
        'keywords': ['design lab'],
        'name': 'design lab MENDELU',
        'address': 'Zemědělská 3, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['zemědělská 1', 'zemědělská 3', 'areál mendelu', 'areal mendelu', 'kampus mendelu'],
        'name': 'Areál MENDELU',
        'address': 'Zemědělská 1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['božetěchova', 'bozetechova', 'fit vut'],
        'name': 'FIT VUT',
        'address': 'Božetěchova 1/2, Brno – Královo Pole',
        'city': 'Brno',
    },
    {
        'keywords': ['fekt vut', 'fekt', 'technická 12', 'technicka 12', 'aula profesora braunera'],
        'name': 'FEKT VUT',
        'address': 'Technická 12, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['fsi vut', 'fsi', 'technická 2896/2', 'technicka 2896/2', 'technická 2'],
        'name': 'FSI VUT',
        'address': 'Technická 2896/2, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['fa vut', 'fakulta architektury vut', 'poříčí 5', 'poříčí 273/5'],
        'name': 'Fakulta architektury VUT',
        'address': 'Poříčí 273/5, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['fast vut', 'fakulta stavební vut', 'veveří 95', 'veveří 331/95'],
        'name': 'Fakulta stavební VUT',
        'address': 'Veveří 331/95, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['fp vut', 'fakulta podnikatelská vut', 'kolejní 4', 'kolejní 2906/4'],
        'name': 'Fakulta podnikatelská VUT',
        'address': 'Kolejní 2906/4, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['fch vut', 'fakulta chemická vut', 'purkyňova 118', 'purkyňova 464/118'],
        'name': 'Fakulta chemická VUT',
        'address': 'Purkyňova 464/118, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['favu vut', 'favu', 'údolní 53', 'údolní 244/53'],
        'name': 'FaVU VUT',
        'address': 'Údolní 244/53, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['pod palackého vrchem', 'koleje ppv', 'ppv vut'],
        'name': 'Koleje Pod Palackého vrchem VUT',
        'address': 'Kolejní 2, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['u kachničky', 'klub u kachničky'],
        'name': 'Studentský klub U Kachničky',
        'address': 'Božetěchova 1/2, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['poříčí', 'porici', 'pedagogická fakulta', 'pedagogicka fakulta', 'pdf muni'],
        'name': 'Pedagogická fakulta MUNI',
        'address': 'Poříčí 7/9, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['botanická 68', 'botanicka', 'fi muni', 'fakulta informatiky'],
        'name': 'Fakulta informatiky MUNI',
        'address': 'Botanická 68a, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['kotlářská', 'kotlarska', 'přf muni', 'prf muni', 'přírodovědecká fakulta', 'prirodovedecka fakulta', 'sci.muni'],
        'name': 'Přírodovědecká fakulta MUNI',
        'address': 'Kotlářská 2, Brno',
        'city': 'Brno',
    },
    {
        'keywords': [
            'kamenice', 'kampus bohunice', 'bohunice', 'ukb', 'recepce ukb', 'vstup ukb',
            'hlavní vstup ukb', 'hlavni vstup ukb', 'b22', 'b11', 'simu', 'simulační centrum',
            'simulacni centrum', 'fn brno', 'fn bohunice', 'jihlavská', 'jihlavska', 'fsps',
            'fsps mu', 'fsps muni', 'fakulta sportovních studií', 'fakulta sportovnich studii',
            'ceitec ukb', 'ceitec bohunice'
        ],
        'name': 'Univerzitní kampus Bohunice',
        'address': 'Kamenice 5, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['arne nováka', 'arne novaka', 'arna nováka', 'arna novaka', 'filozofická fakulta', 'filozoficka fakulta', 'ff mu', 'ff muni'],
        'name': 'Filozofická fakulta MUNI',
        'address': 'Arne Nováka 1/1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['clubco', 'clubco vlněna', 'clubco vlnena', 'vlněna clubco', 'vlnena clubco', 'vlněna brno', 'vlnena brno'],
        'name': 'Clubco Vlněna',
        'address': 'Přízova 5, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['veveří 70', 'veveri 70', 'právnická fakulta', 'pravnicka fakulta', 'prf mu', 'law muni'],
        'name': 'Právnická fakulta MUNI',
        'address': 'Veveří 70, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['lipová 41', 'lipova 41', 'ekonomicko-správní', 'ekonomicko-spravni', 'esf mu', 'esf muni'],
        'name': 'Ekonomicko-správní fakulta MUNI',
        'address': 'Lipová 41a, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['žerotínovo nám', 'zerotinovo nam', 'rektorát muni', 'rektorat muni', 'rektorát mu', 'rektorat mu', 'žerotínovo náměstí'],
        'name': 'Rektorát Masarykovy univerzity',
        'address': 'Žerotínovo náměstí 617/9, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['joštova', 'jostova', 'fss muni'],
        'name': 'Fakulta sociálních studií MUNI',
        'address': 'Joštova 10, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['palackého tř', 'palackeho tr', 'vetuni'],
        'name': 'Kampus VETUNI',
        'address': 'Palackého tř. 1946/1, Brno',
        'city': 'Brno',
    },
    {
        'keywords': ['kasárna černá pole', 'kasarna cerna pole', 'šumavská', 'sumavska', 'unob'],
        'name': 'Areál Univerzity obrany',
        'address': 'Tř. Generála Píky 229/7, Brno',
        'city': 'Brno',
    },
]


def clean_emoji(text: str) -> str:
    """Removes emojis and symbols from text per BrnoAkce guidelines."""
    if not text:
        return ""
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


def normalize_text(text: str) -> str:
    """Normalizes whitespace and replaces non-breaking spaces."""
    if not text:
        return ""
    cleaned = text.replace("\xa0", " ")
    return re.sub(r"\s+", " ", cleaned).strip()


def clean_title(raw_title: str, max_length: int = 90) -> str:
    """Cleans event title, strips common social media/marketing prefixes, and trims."""
    title = clean_emoji(raw_title)
    title = re.sub(r"^(?:Je tu|Přichází|Zveme vás na:?|Akce:?|Top akce:?)\s+", "", title, flags=re.IGNORECASE).strip()
    # Strip trailing punctuation artifacts
    title = re.sub(r"\s*//+\s*$", "", title).strip()
    if len(title) > max_length:
        title = title[:max_length].rsplit(" ", 1)[0].strip()
    return title


def extract_explicit_time_from_text(text: str) -> Optional[Tuple[int, int]]:
    """
    Extracts the first explicit hour and minute from text without confusing with dates.
    Handles 'od 21:00', 'v 19:30', '19.00 hod', '21:00', etc.
    """
    if not text:
        return None

    norm = normalize_text(text)

    # 1. Colon pattern with or without preposition: 'v 19:00', 'od 21:00', '21:00'
    m = re.search(r"\b(?:v|ve|od|at)?\s*(\d{1,2}):(\d{2})\b", norm, re.IGNORECASE)
    if m:
        h, mn = int(m.group(1)), int(m.group(2))
        if 0 <= h <= 23 and 0 <= mn <= 59:
            return h, mn

    # 2. Dot time with explicit time preposition e.g. 'v 19.00' or 'od 18.30'
    m = re.search(r"\b(?:v|ve|od|at)\s*(\d{1,2})\.(\d{2})\b", norm, re.IGNORECASE)
    if m:
        h, mn = int(m.group(1)), int(m.group(2))
        if 0 <= h <= 23 and 0 <= mn <= 59:
            return h, mn

    # 3. Dot time with hour unit suffix e.g. '19.00 hod' or '18.30h'
    m = re.search(r"\b(\d{1,2})\.(\d{2})\s*(?:hod|h|hodin)\b", norm, re.IGNORECASE)
    if m:
        h, mn = int(m.group(1)), int(m.group(2))
        if 0 <= h <= 23 and 0 <= mn <= 59:
            return h, mn

    # 4. Hour with preposition and unit e.g. 'v 19h', 'od 18 hodin'
    m = re.search(r"\b(?:v|ve|od|at)\s*(\d{1,2})\s*(?:h|hod|hodin)\b", norm, re.IGNORECASE)
    if m:
        h = int(m.group(1))
        if 0 <= h <= 23:
            return h, 0

    return None


def extract_time_range_from_text(text: str) -> Tuple[Optional[Tuple[int, int]], Optional[Tuple[int, int]]]:
    """
    Extracts start time and optional end time (start_h, start_m), (end_h, end_m)
    from text strings like '9:00 – 15:00', '16:00 - 19:00', 'od 21:00'.
    """
    if not text:
        return None, None

    norm = normalize_text(text)

    # Range pattern: HH:MM [–- do až] HH:MM
    range_match = re.search(r"\b(\d{1,2}):(\d{2})\s*(?:[–—-]|do|až)\s*(\d{1,2}):(\d{2})\b", norm)
    if range_match:
        sh, sm = int(range_match.group(1)), int(range_match.group(2))
        eh, em = int(range_match.group(3)), int(range_match.group(4))
        if 0 <= sh <= 23 and 0 <= sm <= 59 and 0 <= eh <= 23 and 0 <= em <= 59:
            return (sh, sm), (eh, em)

    # Single time
    single = extract_explicit_time_from_text(norm)
    return single, None


def extract_explicit_date_from_text(text: str, default_year: Optional[int] = None) -> Optional[datetime]:
    """
    Extracts explicit Czech date (DD.MM.YYYY, DD.MM., or DD. [czech_month] [YYYY]) from text.
    """
    if not text:
        return None

    if default_year is None:
        default_year = datetime.now().year

    norm = normalize_text(text)

    # 1. Pattern with Czech month word: e.g. 24. listopadu 2026, 2. prosince
    month_pattern = "|".join(re.escape(k) for k in CZECH_MONTHS.keys())
    m_word = re.search(r"\b(\d{1,2})\.\s*(" + month_pattern + r")(?:\s+(\d{4}))?\b", norm, re.IGNORECASE)
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
    m_num = re.search(r"(?<!\d)(\d{1,2})\.\s*(\d{1,2})\.(?:\s*(\d{4}))?(?!\d)", norm)
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


def extract_venue_and_address(text: str, default_venue: str = "Brno") -> Tuple[str, Optional[str]]:
    """
    Matches text against the canonical Brno student venue registry.
    Returns (venue_display_string, optional_address).
    """
    if not text:
        return default_venue, None

    cleaned = clean_emoji(text)
    lower = cleaned.lower()

    # 1. Search for explicit known venues
    for v in KNOWN_VENUES:
        for kw in v['keywords']:
            # Use word boundaries for exact match
            pattern = r'\b' + re.escape(kw) + r'\b'
            if re.search(pattern, lower):
                display = f"{v['name']}, {v['address']}" if v['address'] else v['name']
                return display, v.get('address')

    # 2. Search for explicit "Kde / Místo / 📍" markers on raw text first so emojis aren't lost
    venue_match = re.search(r"(?:Kde|Místo|Místo konání|Lokalita|[📍📌🗺️])\s*:?\s*([^\n\r]+)", text, re.IGNORECASE)
    if venue_match:
        candidate = clean_emoji(venue_match.group(1).strip())
        candidate = re.sub(r"^(?:ve?|at|in|do|na)\s+", "", candidate, flags=re.IGNORECASE).strip()
        candidate = re.sub(r"\s*//+\s*$", "", candidate).strip()
        if candidate and len(candidate) > 2:
            # Check candidate against known venues again
            cand_lower = candidate.lower()
            for v in KNOWN_VENUES:
                for kw in v['keywords']:
                    if kw in cand_lower:
                        display = f"{v['name']}, {v['address']}" if v['address'] else v['name']
                        return display, v.get('address')
            return candidate, None

    # 3. Search for street and number in Brno: e.g. "Kounicova 42, Brno" or "Sukova 4"
    non_street_words = {
        "academy", "prezenčně", "prezencne", "online", "listopadu", "prosince", "ledna",
        "února", "unora", "března", "brezna", "dubna", "května", "kvetna", "června", "cervna",
        "července", "cervence", "srpna", "září", "zari", "října", "rijna", "ročník", "rocnik",
        "pátek", "patek", "sobota", "neděle", "nedele", "pondělí", "pondeli", "úterý", "utery",
        "středa", "streda", "čtvrtek", "ctvrtek", "cena", "vstup", "vstupné", "vstupne",
        "od", "do", "ve", "v", "top", "listopad", "prosinec", "leden", "únor", "březen",
        "na", "k", "ke", "o", "u", "za", "přes", "pod", "pro", "se", "si", "přijďte", "prijdte",
        "přijď", "prijd", "více", "vice", "čtěte", "ctete", "vložil", "vlozil", "změna", "zmena",
        "hodin", "hod", "let", "patro", "kolo", "místnost", "mistnost"
    }
    for addr_match in re.finditer(r"\b([A-ZÁ-Ž][a-zčďéěíňóřšťúůýž]+(?:\s+[a-zčďéěíňóřšťúůýž]+)?\s+\d+(?:[a-zA-Z]|\/\d+)?)(?:,\s*([A-ZÁ-Ž][a-zčďéěíňóřšťúůýž]+))?\b", cleaned):
        street_cand = addr_match.group(1).strip()
        parts = street_cand.lower().split()
        if any(p in non_street_words for p in parts):
            continue
        end_pos = addr_match.end()
        post_match = cleaned[end_pos:end_pos+20].lower()
        if re.match(r"^\s*\.?\s*(?:ročník|rocnik|patro|kolo|hodin|hod\b|let\b)", post_match):
            continue
        m_num = re.search(r"\d+", street_cand)
        if m_num:
            num = int(m_num.group(0))
            if 1900 <= num <= 2100:  # year
                continue
        city = addr_match.group(2).strip() if addr_match.group(2) else "Brno"
        return f"{street_cand}, {city}", f"{street_cand}, {city}"

    # 4. If the input text is a clean short location candidate (e.g. from JSON-LD or event metadata),
    # preserve the exact venue name instead of overwriting with a generic campus/rectorate!
    clean_stripped = cleaned.strip()
    if 3 < len(clean_stripped) < 120 and "\n" not in clean_stripped:
        lower_s = clean_stripped.lower()
        generic_names = {"masarykova univerzita", "masarykova univerzita, brno", "muni", "mendelu", "vut", "unob", "vetuni", "brno", "brno centrum"}
        is_bad_venue = (
            lower_s.startswith("událost") or
            bool(re.search(r"\d+\.\s*[–—-]\s*\d+\.", lower_s)) or
            bool(re.search(r"\b(ročník|rocnik|konference|workshop|seminář|seminar|přednáška|prednaska|festival)\b", lower_s)) or
            bool(re.search(r"\b(prezenčně|prezencne|online|hybridně|hybridne)\b", lower_s))
        )
        if lower_s not in generic_names and not is_bad_venue:
            return clean_stripped, None

    return default_venue, None


def extract_price_info(text: str, ticket_url: Optional[str] = None) -> Tuple[str, Optional[int]]:
    """
    Extracts price type ('free' vs 'paid') and optional integer amount in CZK.
    """
    if not text:
        return ("paid", None) if ticket_url else ("free", None)

    lower = text.lower()

    # Free keywords
    if any(k in lower for k in ["vstup zdarma", "zdarma", "volný vstup", "vstup volný", "free entry", "bezplatn", "bez poplatku"]):
        return "free", None

    # Paid keyword or pattern with numbers: e.g. "Vstup: 250,-", "80 Kč", "150 CZK", "Startovné je 80 Kč", "Poplatek za kurz je 1990 Kč"
    price_match = re.search(r"(?:cena|poplatek|kurzovné|kurzovne|vstup|vstupné|vstupne|startovné|startovne|ticket|tickets)(?:[^:\n]*:)?\s*(?:[^\d\n]*\s*)?(\d+)\s*(?:kč|czk|,-)", lower, re.IGNORECASE)
    if price_match:
        return "paid", int(price_match.group(1))

    num_czk = re.search(r"\b(\d+)\s*(?:kč|czk)\b", lower)
    if num_czk:
        amt = int(num_czk.group(1))
        if amt > 0 and amt != 2026 and amt != 2027:
            return "paid", amt

    if ticket_url:
        return "paid", None

    return "free", None


def detect_event_category(text: str) -> str:
    """Classifies event category slug from Czech keywords."""
    lower = text.lower()
    if any(k in lower for k in ['turnaj', 'beerpong', 'fotbal', 'běh', 'sport', 'workout', 'volejbal', 'liga']):
        return 'turnaje-sport'
    if any(k in lower for k in ['kvíz', 'kviz', 'quiz', 'quizz', 'deskovk', 'hry', 'lan party', 'game night', 'gamenight']):
        return 'kvizy-hry'
    if any(k in lower for k in ['párty', 'party', 'večírek', 'vecirek', 'grilovačk', 'otvírák', 'noc', 'klub', 'rave']):
        return 'party-kluby'
    if any(k in lower for k in ['přednášk', 'prednask', 'workshop', 'seminář', 'seminar', 'konferenc', 'kurz', 'veletrh', 'hackathon', 'diskuze', 'talk']):
        return 'prednasky-kariera'
    return 'kultura'


def is_administrative_or_irrelevant(text: str) -> bool:
    """
    Detects administrative notices, admission deadlines, or academic non-events.
    """
    lower = text.lower()
    blocked_phrases = [
        "podávání přihlášek",
        "podavani prihlasek",
        "přijímací řízení",
        "prijimaci rizeni",
        "výběrové řízení",
        "vyberove rizeni",
        "státní závěrečné zkoušky",
        "statni zaverecne zkousky",
        "harmonogram akademického roku",
        "harmonogram akademickeho roku",
        "zasedání akademického senátu",
        "zasedani akademickeho senatu",
        "zápis do studia",
        "zapis do studia",
        "lhůta pro odevzdání",
        "lhuta pro odevzdani",
        "dodatečné přijímací",
        "kurz celoživotního vzdělávání",
        "celoživotní vzdělávání",
        "kurz cžv",
        "kurzy cžv",
        "cžv",
        "vzdělávání po celý život",
        "pro středoškoláky",
        "pro studenty středních škol",
        "pro studenty sš",
        "pro žáky zš",
        "pro žáky sš",
        "dny praxe",
        "düsseldorf",
    ]
    return any(p in lower for p in blocked_phrases)


def is_outside_brno(location_str: str = "", text: str = "") -> bool:
    """
    Detects if an event is explicitly located outside of Brno.
    BrnoAkce is strictly for events taking place in Brno.
    Events hosted in Lednice, Valtice, Telč, Prague, Ostrava, etc. must be skipped.
    """
    loc_lower = (location_str or "").lower()
    text_lower = (text or "").lower()
    combined = f"{loc_lower} {text_lower}"

    # 1. Lednice / Valtice (e.g. ZF MENDELU detached faculty)
    if any(k in loc_lower for k in ["lednice", "valtická", "valticka", "zf mendelu"]):
        return True
    if any(k in combined for k in ["v lednici", "areál lednice", "areal lednice", "zámek lednice", "zamek lednice"]):
        return True

    # 2. Telč (Univerzitní centrum Telč MUNI)
    if any(k in loc_lower for k in ["telč", "telc"]):
        return True
    if "v telči" in combined or "v telci" in combined or "centrum telč" in combined:
        return True

    # 3. Other major Czech/Slovak cities in location
    non_brno_cities = [
        "praha", "praze", "prague",
        "olomouc", "olomouci",
        "ostrava", "ostravě", "ostrave",
        "zlín", "zlin", "zlíně", "zline",
        "bratislava", "bratislavě", "bratislave",
        "plzeň", "plzen", "plzni",
        "hradec králové", "hradeckralove", "pardubice",
        "české budějovice", "ceske budejovice",
        "liberec", "ústí nad labem",
        "vídeň", "viden", "wien", "vienna",
        "mikulov", "břeclav", "breclav", "znojmo", "vyškov", "vyskov", "blansko"
    ]
    for city in non_brno_cities:
        if re.search(r'\b' + re.escape(city) + r'\b', combined):
            # Check against street names in Brno (e.g. Bratislavská, Vídeňská, Olomoucká, Pražská)
            if re.search(r'\b(bratislavsk|vídeňsk|vidensk|olomouck|pražsk|prazsk|znojemsk|hradeck|jihlavsk|vyškovsk|vyskovsk)[áéy]\b', combined):
                continue
            return True

    return False
