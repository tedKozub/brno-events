#!/usr/bin/env python3
import unittest
import os
import sys
import json
import tempfile

scrapers_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
if scrapers_dir not in sys.path:
    sys.path.insert(0, scrapers_dir)

from card_slicer import slice_raw_content, load_hash_cache, save_hash_cache
from ai_batch_extractor import build_batch_prompt, AGY_SCHEMA


class TestCardSlicerAndAiExtractor(unittest.TestCase):

    def setUp(self):
        self.fixtures_dir = os.path.join(scrapers_dir, "fixtures")
        self.temp_dir = tempfile.TemporaryDirectory()
        self.cache_path = os.path.join(self.temp_dir.name, ".test_hashes.json")

    def tearDown(self):
        self.temp_dir.cleanup()

    def test_slice_mendelu_fixture(self):
        fixture_path = os.path.join(self.fixtures_dir, "sample_mendelu.html")
        with open(fixture_path, "r", encoding="utf-8") as f:
            html = f.read()

        cards = slice_raw_content(html, "web_mendelu", "https://mendelu.cz/akce/", cache_path=self.cache_path)
        self.assertGreater(len(cards), 0)

        first = cards[0]
        self.assertIn("card_id", first)
        self.assertIn("raw_text", first)
        self.assertIn("sha256", first)
        self.assertTrue(first["is_new_or_changed"])

    def test_idempotence_with_hash_cache(self):
        sample_text = "<div class='event-card'><a href='/akce/test-1'><h3>Test Akce 1</h3><p>Popis akce v Brně</p></a></div>"
        
        # 1st run: new card
        cards_1 = slice_raw_content(sample_text, "web_mendelu", "https://mendelu.cz", cache_path=self.cache_path)
        self.assertEqual(len(cards_1), 1)
        self.assertTrue(cards_1[0]["is_new_or_changed"])

        # Simulate cache save
        cache = {cards_1[0]["card_id"]: cards_1[0]["sha256"]}
        save_hash_cache(self.cache_path, cache)

        # 2nd run: unchanged card
        cards_2 = slice_raw_content(sample_text, "web_mendelu", "https://mendelu.cz", cache_path=self.cache_path)
        self.assertEqual(len(cards_2), 1)
        self.assertFalse(cards_2[0]["is_new_or_changed"])

        # 3rd run: modified content
        modified_text = "<div class='event-card'><a href='/akce/test-1'><h3>Test Akce 1 - Nový čas</h3><p>Popis akce v Brně</p></a></div>"
        cards_3 = slice_raw_content(modified_text, "web_mendelu", "https://mendelu.cz", cache_path=self.cache_path)
        self.assertEqual(len(cards_3), 1)
        self.assertTrue(cards_3[0]["is_new_or_changed"])

    def test_build_batch_prompt_and_schema(self):
        cards = [
            {"card_id": "test-1", "source_url": "https://test.cz/1", "raw_text": "Akce v aule FEKT VUT Technická 12, Brno"},
            {"card_id": "test-2", "source_url": "https://test.cz/2", "raw_text": "Akce ZF Lednice"},
        ]
        prompt = build_batch_prompt(cards, university="VUT")
        self.assertIn("Technická 12", prompt)
        self.assertIn("Lednice", prompt)
        self.assertIn("is_in_brno", prompt)

        self.assertEqual("object", AGY_SCHEMA["type"])
        self.assertIn("events", AGY_SCHEMA["properties"])


if __name__ == "__main__":
    unittest.main()
