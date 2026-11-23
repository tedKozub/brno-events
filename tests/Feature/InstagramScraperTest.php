<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\ScrapeSource;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class InstagramScraperTest extends TestCase
{
    use DatabaseTransactions;

    public function test_instagram_source_can_be_registered(): void
    {
        $source = ScrapeSource::firstOrCreate(
            ['target_identifier' => 'sppdf-muni-instagram'],
            [
                'name' => 'Spolek přátel PdF MUNI (Instagram)',
                'type' => 'web_instagram',
                'university' => 'MUNI',
                'url' => 'sppdf_muni',
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas('scrape_sources', [
            'target_identifier' => 'sppdf-muni-instagram',
            'type' => 'web_instagram',
        ]);
    }

    public function test_recurring_weekday_without_explicit_calendar_date_is_ignored(): void
    {
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $script = <<<'PY'
from datetime import datetime
from scrapers.parsers.instagram_parser import (
    extract_event_from_caption,
    sanitize_extracted_event_date
)

# Vague recurring weekday post without explicit calendar date MUST be ignored
caption = "SP-PDF BEERPONG LIGA 10. SEZONA\nKOMA LOUNGE & BAR\nSTREDY 19:30"
ev = extract_event_from_caption(caption, "https://instagram.com/sppdf_muni")
assert ev is None, f"Expected None for vague beerpong weekday but got {ev}"

hallucinated = {
    'title': 'SP-PDF BEERPONG LIGA 10. SEZONA',
    'starts_at': '2026-11-18 19:30:00',
    'description': 'Stredy v Koma Lounge'
}
sanitized = sanitize_extracted_event_date(hallucinated, 'STREDY 19:30')
assert sanitized is None, f"Expected None from sanitizer for vague weekday without date but got {sanitized}"

# If caption HAS an explicit date, it is accepted
with_date = "SP-PDF BEERPONG FINALE\n18.11.2026 v 19:30 v Koma Lounge"
ev_date = extract_event_from_caption(with_date, "https://instagram.com/sppdf_muni")
assert ev_date is not None
assert ev_date['starts_at'] == '2026-11-18 19:30:00'

print("OK_VAGUE_WEEKDAY_IGNORED")
PY;

        $proc = \Illuminate\Support\Facades\Process::path(base_path())
            ->env(['PYTHONPATH' => base_path()])
            ->run([$pythonBin, '-c', $script]);

        $this->assertTrue($proc->successful(), $proc->errorOutput() ?: $proc->output());
        $this->assertStringContainsString('OK_VAGUE_WEEKDAY_IGNORED', $proc->output());
    }

    public function test_explicit_calendar_date_event_can_be_created(): void
    {
        $event = Event::create([
            'title' => 'SP-PDF BEERPONG FINÁLE',
            'slug' => 'sp-pdf-beerpong-finale-' . uniqid(),
            'starts_at' => Carbon::parse('2026-11-18 19:30:00'),
            'source_url' => 'https://www.instagram.com/sppdf_muni/#post-d69dc02efbac',
            'status' => 'published',
        ]);

        $this->assertEquals(Carbon::WEDNESDAY, $event->starts_at->dayOfWeek);
        $this->assertEquals('2026-11-18 19:30:00', $event->starts_at->format('Y-m-d H:i:s'));
    }

    public function test_explicit_numerical_date_takes_absolute_precedence_over_guessing_and_weekdays(): void
    {
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $script = <<<'PY'
from datetime import datetime
from scrapers.parsers.instagram_parser import (
    extract_event_from_caption,
    sanitize_extracted_event_date,
    extract_explicit_date_from_text,
    extract_explicit_time_from_text,
)

caption = (
    "Znovu přichází Váš oblíbený PUB QUIZZ! Přijďte své fantastické znalosti otestovat "
    "10.11.2026 v 19:00 do Brněnského Koma Lounge & Baru. Startovné je 80 Kč za hráče. "
    "Těšíme se na Vás!"
)

# 1. Test direct heuristic extraction from caption
ev = extract_event_from_caption(caption, "https://instagram.com/sppdf_muni")
assert ev is not None, "Event extraction returned None"
assert ev['starts_at'] == '2026-11-10 19:00:00', f"Expected 2026-11-10 19:00:00 but got {ev['starts_at']}"
assert ev['price_amount'] == 80, f"Expected 80 but got {ev['price_amount']}"
assert "Koma Lounge" in ev['location_name'], f"Expected Koma Lounge but got {ev['location_name']}"

# 2. Test sanitizer overrides LLM hallucinated / shifted date (e.g. 18.11.) with explicit date from text
hallucinated = {
    'title': 'pohádkový PUB QUIZZ!',
    'starts_at': '2026-11-18 19:00:00',
    'description': caption,
}
sanitized = sanitize_extracted_event_date(hallucinated, caption)
assert sanitized['starts_at'] == '2026-11-10 19:00:00', (
    f"Sanitizer failed to override hallucinated date: {sanitized['starts_at']}"
)

# 3. Test Czech month word date e.g. 25. listopadu
caption_month_word = "Kulturní večer proběhne 25. listopadu v 17:30 v KC Babylon."
ev_month = extract_event_from_caption(caption_month_word, "https://instagram.com/sppdf_muni")
assert ev_month is not None
dt_month = datetime.strptime(ev_month['starts_at'], "%Y-%m-%d %H:%M:%S")
assert dt_month.month == 11 and dt_month.day == 25 and dt_month.hour == 17 and dt_month.minute == 30, (
    f"Unexpected starts_at for month word: {ev_month['starts_at']}"
)

print("OK_EXPLICIT_DATE")
PY;

        $proc = \Illuminate\Support\Facades\Process::path(base_path())
            ->env(['PYTHONPATH' => base_path()])
            ->run([$pythonBin, '-c', $script]);

        $this->assertTrue($proc->successful(), $proc->errorOutput() ?: $proc->output());
        $this->assertStringContainsString('OK_EXPLICIT_DATE', $proc->output());
    }

    public function test_kreativni_workshop_poster_preserves_11_11_and_never_shifts_to_18_11(): void
    {
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $script = <<<'PY'
from datetime import datetime
from scrapers.parsers.instagram_parser import (
    sanitize_extracted_event_date,
    extract_explicit_date_from_text,
)

# Simulace extrakce z plakátu Kreativní workshop (11.11.2026 od 16:00 v Café Morgal)
extracted = {
    'title': 'Kreativní workshop',
    'starts_at': '2026-11-11 16:00:00',
    'location_name': 'Café Morgal',
    'description': 'Kreativní workshop pomaluj si květináč a zasaď bylinku 11.11.2026 od 16:00 v Café Morgal'
}

# I když je dnešní datum 18.11. (středa), datum akce z plakátu MUSÍ zůstat přesně 11.11.
now_sim = datetime(2026, 11, 18, 12, 0, 0)
sanitized = sanitize_extracted_event_date(extracted, "V rámci workshopu si vytvoříš vlastní květináč", now=now_sim)
assert sanitized is not None, "Sanitizer should not discard event"
assert sanitized['starts_at'] == '2026-11-11 16:00:00', f"Starts at was corrupted to {sanitized['starts_at']}"

print("OK_WORKSHOP_DATE_PRESERVED")
PY;

        $proc = \Illuminate\Support\Facades\Process::path(base_path())
            ->env(['PYTHONPATH' => base_path()])
            ->run([$pythonBin, '-c', $script]);

        $this->assertTrue($proc->successful(), $proc->errorOutput() ?: $proc->output());
        $this->assertStringContainsString('OK_WORKSHOP_DATE_PRESERVED', $proc->output());

        // Ověření deduplikace v ScrapeEventsCommand: stejná akce se stejným názvem v rozmezí 7 dní se neduplikuje
        $org = \App\Models\Organization::firstOrCreate(
            ['slug' => 'sppdf-muni-test'],
            ['name' => 'SP-PdF MUNI Test', 'university' => 'MUNI']
        );

        $existing = Event::create([
            'organization_id' => $org->id,
            'title' => 'Kreativní workshop',
            'slug' => 'kreativni-workshop-test-' . uniqid(),
            'starts_at' => Carbon::parse('2026-11-11 16:00:00'),
            'source_url' => 'https://www.instagram.com/sppdf_muni/#post-8b0f54c7c7a2',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('events', [
            'id' => $existing->id,
            'title' => 'Kreativní workshop',
            'starts_at' => '2026-11-11 16:00:00',
        ]);
    }

    public function test_openai_vision_error_does_not_pollute_stdout(): void
    {
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $script = <<<'PY'
import sys
import io
from scrapers.parsers.instagram_parser import extract_with_openai_vision

# Intercept stdout and stderr
old_stdout = sys.stdout
old_stderr = sys.stderr
sys.stdout = io.StringIO()
sys.stderr = io.StringIO()

try:
    # Use dummy invalid API key to trigger error handling
    res, is_fatal = extract_with_openai_vision('/nonexistent/path.jpg', 'caption', 'invalid-key')
    stdout_val = sys.stdout.getvalue()
    stderr_val = sys.stderr.getvalue()
finally:
    sys.stdout = old_stdout
    sys.stderr = old_stderr

assert stdout_val == '', f"stdout must remain completely empty on error, but got: {stdout_val}"
assert res is None, "Result should be None on error"
print("OK_STDOUT_CLEAN")
PY;

        $proc = \Illuminate\Support\Facades\Process::path(base_path())
            ->env(['PYTHONPATH' => base_path()])
            ->run([$pythonBin, '-c', $script]);

        $this->assertTrue($proc->successful(), $proc->errorOutput() ?: $proc->output());
        $this->assertStringContainsString('OK_STDOUT_CLEAN', $proc->output());
    }

    public function test_json_decoder_extracts_json_with_prefix_noise(): void
    {
        $noisyOutput = "Warning: some upstream library notice\nOpenAI error 429: quota exhausted\n{\n  \"status\": \"success\",\n  \"count\": 1,\n  \"events\": []\n}\n";

        $rawOutput = trim($noisyOutput);
        $json = json_decode($rawOutput, true);
        if ($json === null) {
            $startPos = strpos($rawOutput, '{');
            $endPos = strrpos($rawOutput, '}');
            if ($startPos !== false && $endPos !== false && $endPos > $startPos) {
                $json = json_decode(substr($rawOutput, $startPos, $endPos - $startPos + 1), true);
            }
        }

        $this->assertIsArray($json);
        $this->assertEquals('success', $json['status']);
        $this->assertCount(0, $json['events']);
    }
}
