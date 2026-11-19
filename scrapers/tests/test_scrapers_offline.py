import os
import sys
import json
import subprocess
import unittest

# Ensure paths
CUR_DIR = os.path.dirname(os.path.abspath(__file__))
SCRAPERS_DIR = os.path.dirname(CUR_DIR)
PROJECT_ROOT = os.path.dirname(SCRAPERS_DIR)

if SCRAPERS_DIR not in sys.path:
    sys.path.insert(0, SCRAPERS_DIR)
if PROJECT_ROOT not in sys.path:
    sys.path.insert(0, PROJECT_ROOT)

from parsers.ical_parser import parse_ical
from parsers.rss_parser import parse_rss
from parsers.web_parsers import (
    parse_mendelu,
    parse_fit_vut,
    parse_unob,
    parse_muni,
    parse_generic_html,
    is_administrative_or_irrelevant,
)
from parsers.instagram_parser import (
    extract_event_from_caption,
    sanitize_extracted_event_date,
)
from parsers.facebook_parser import extract_event_from_text


class TestScrapersOffline(unittest.TestCase):
    def setUp(self):
        self.fixtures_dir = os.path.join(SCRAPERS_DIR, "fixtures")

    def test_import_all_modules_without_error(self):
        """Regression test: verify all modules import without ModuleNotFoundError."""
        import parsers.ical_parser
        import parsers.rss_parser
        import parsers.instagram_parser
        import parsers.facebook_parser
        import parsers.web_parsers
        import main
        self.assertIsNotNone(main)

    def test_parse_ical_offline(self):
        ics_path = os.path.join(self.fixtures_dir, "sample_student_events.ics")
        with open(ics_path, "rb") as f:
            content = f.read()
        events = parse_ical(content, source_url=ics_path)
        self.assertGreaterEqual(len(events), 1)
        self.assertIn("title", events[0])
        self.assertIn("starts_at", events[0])

    def test_parse_rss_offline_and_filter_administrative(self):
        rss_path = os.path.join(self.fixtures_dir, "sample_feed.xml")
        with open(rss_path, "rb") as f:
            content = f.read()
        events = parse_rss(content, source_url=rss_path)
        # Should parse 2 valid events and filter out "Podávání přihlášek"
        self.assertEqual(len(events), 2)
        titles = [e["title"] for e in events]
        self.assertTrue(any("Ples" in t for t in titles))
        self.assertTrue(any("Přednáška" in t for t in titles))
        self.assertFalse(any("přihlášek" in t.lower() for t in titles))

    def test_parse_mendelu_offline(self):
        path = os.path.join(self.fixtures_dir, "sample_mendelu.html")
        with open(path, "r", encoding="utf-8") as f:
            html = f.read()
        events = parse_mendelu(html, base_url="https://mendelu.cz/akce/")
        self.assertGreaterEqual(len(events), 1)
        
        # Verify festival
        festival = next((e for e in events if "festival" in e["title"].lower()), None)
        self.assertIsNotNone(festival)
        
        # Verify Lednice event is skipped because BrnoAkce only accepts events in Brno
        lednice = next((e for e in events if "zahradnictví" in e["title"].lower()), None)
        self.assertIsNone(lednice, "Events outside Brno (Lednice) must be skipped")

        # Administrative card must be filtered out
        for e in events:
            self.assertFalse(is_administrative_or_irrelevant(e["title"]))

    def test_outside_brno_detection(self):
        from parsers.common import is_outside_brno
        self.assertTrue(is_outside_brno("ZF MENDELU, Valtická 337, Lednice"))
        self.assertTrue(is_outside_brno("Zámek Lednice"))
        self.assertTrue(is_outside_brno("Univerzitní centrum Telč"))
        self.assertTrue(is_outside_brno("Praha 1 - Staré Město"))
        self.assertTrue(is_outside_brno("Olomouc centrum"))
        self.assertFalse(is_outside_brno("Areál MENDELU, Zemědělská 1, Brno"))
        self.assertFalse(is_outside_brno("Poříčí 7/9, Brno"))
        self.assertFalse(is_outside_brno("Bratislavská 15, Brno"))
        self.assertFalse(is_outside_brno("Vídeňská 42, Brno"))

    def test_parse_fit_vut_offline(self):
        path = os.path.join(self.fixtures_dir, "sample_fit_vut.html")
        with open(path, "r", encoding="utf-8") as f:
            html = f.read()
        events = parse_fit_vut(html, base_url="https://fit.vut.cz/calendar/")
        self.assertEqual(len(events), 1)
        self.assertIn("Hackathon", events[0]["title"])
        self.assertIn("Božetěchova", events[0]["location_name"])

    def test_parse_unob_offline(self):
        path = os.path.join(self.fixtures_dir, "sample_unob.html")
        with open(path, "r", encoding="utf-8") as f:
            html = f.read()
        events = parse_unob(html, base_url="https://unob.cz/akce")
        self.assertEqual(len(events), 2)
        titles = [e["title"] for e in events]
        self.assertTrue(any("Den otevřených dveří" in t for t in titles))
        self.assertTrue(any("Slavnostní vyřazení" in t for t in titles))

    def test_parse_muni_offline(self):
        path = os.path.join(self.fixtures_dir, "sample_muni.html")
        with open(path, "r", encoding="utf-8") as f:
            html = f.read()
        events = parse_muni(html, base_url="https://muni.cz/kalendar")
        self.assertEqual(len(events), 1)
        self.assertIn("debata", events[0]["title"].lower())
        self.assertIn("2026-11-27", events[0]["starts_at"])

    def test_instagram_nlp_offline(self):
        caption = "PUB QUIZZ 24.11.2026 v 19:00 v Koma Lounge & Bar! Vstup 80 Kč."
        ev = extract_event_from_caption(caption, base_url="https://instagram.com/sppdf_muni")
        self.assertIsNotNone(ev)
        ev = sanitize_extracted_event_date(ev, caption)
        self.assertIsNotNone(ev)
        self.assertIn("2026-11-24", ev["starts_at"])
        self.assertIn("Koma Lounge", ev["location_name"])

        # Vague recurring without date MUST be ignored
        vague = "BEERPONG LIGA STREDY 19:30"
        self.assertIsNone(extract_event_from_caption(vague, base_url="https://instagram.com/sppdf_muni"))

    def test_facebook_nlp_offline(self):
        text = "Game Night v Kavárně Trojka! 25.11.2026 od 19:00. Vstup zdarma."
        ev = extract_event_from_text(text, base_url="https://facebook.com/sppdfmuni", university="MUNI")
        self.assertIsNotNone(ev)
        self.assertIn("Trojka", ev["location_name"])
        self.assertEqual(ev["price_type"], "free")

        # Test regression: Pyzamova party 2.12. at Maison Club
        pyzamova_text = """Je tu Pyžamová párty a s ní naše každoroční akce Vše za 25,-!
Maison Club & Lounge
2. 12. od 21:00
Vstup: Základní 250,- / v dresscodu 200,-"""
        ev2 = extract_event_from_text(pyzamova_text, base_url="https://facebook.com/events/1496076759082027/", university="MUNI")
        self.assertIsNotNone(ev2)
        self.assertIn("2026-12-02 21:00:00", ev2["starts_at"])
        self.assertIn("Maison", ev2["location_name"])
        self.assertEqual("paid", ev2["price_type"])
        self.assertEqual(250, ev2["price_amount"])

    def test_nova_radnice_and_custom_location_preservation(self):
        from parsers.common import extract_venue_and_address
        v, a = extract_venue_and_address("Nová radnice, Brno", default_venue="Brno centrum")
        self.assertIn("Nová radnice", v)
        self.assertIn("Dominikánské náměstí 1", v)

        # Custom non-listed location must be preserved, never converted to generic university rectorate
        v2, a2 = extract_venue_and_address("I. neurologická klinika ve FN u sv. Anny", default_venue="Brno centrum")
        self.assertEqual("I. neurologická klinika ve FN u sv. Anny", v2)
        self.assertNotIn("Rektorát", v2)

        # Empty or generic university fallback must become Brno centrum
        v3, a3 = extract_venue_and_address("Masarykova univerzita", default_venue="Brno centrum")
        self.assertEqual("Brno centrum", v3)

    def test_main_cli_dry_run_all_types(self):
        """Executes main.py with --dry-run for each parser type via subprocess."""
        python_bin = sys.executable
        main_py = os.path.join(SCRAPERS_DIR, "main.py")

        types_to_test = [
            ("web_ical", "sample_student_events.ics"),
            ("web_rss", "https://example.com/rss"),
            ("web_mendelu", "https://mendelu.cz/akce/"),
            ("web_fit_vut", "https://fit.vut.cz/calendar"),
            ("web_unob", "https://unob.cz/akce"),
            ("web_muni", "https://muni.cz/kalendar"),
            ("web_instagram", "sppdf_muni"),
            ("web_facebook", "sppdfmuni"),
        ]

        for p_type, url in types_to_test:
            with self.subTest(parser_type=p_type):
                cmd = [
                    python_bin,
                    main_py,
                    "--url", url,
                    "--type", p_type,
                    "--dry-run"
                ]
                proc = subprocess.run(
                    cmd,
                    cwd=PROJECT_ROOT,
                    capture_output=True,
                    text=True,
                    timeout=10
                )
                self.assertEqual(
                    proc.returncode, 0,
                    f"CLI dry-run failed for type {p_type}:\nSTDOUT: {proc.stdout}\nSTDERR: {proc.stderr}"
                )
                data = json.loads(proc.stdout)
                self.assertEqual(data.get("status"), "success")
                self.assertIsInstance(data.get("events"), list)


if __name__ == "__main__":
    unittest.main()
