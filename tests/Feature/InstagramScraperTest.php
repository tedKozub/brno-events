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

    public function test_czech_recurring_weekday_caption_resolves_to_correct_upcoming_day(): void
    {
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $script = <<<'PY'
from datetime import datetime
from scrapers.parsers.instagram_parser import (
    extract_event_from_caption,
    resolve_next_weekday,
    sanitize_extracted_event_date
)

ref = datetime(2026, 11, 13, 10, 0, 0) # Friday
wed = resolve_next_weekday(2, 19, 30, now=ref)
assert wed == datetime(2026, 11, 18, 19, 30, 0)

caption = "SP-PDF BEERPONG LIGA 10. SEZONA\nKOMA LOUNGE & BAR\nSTREDY 19:30"
ev = extract_event_from_caption(caption, "https://instagram.com/sppdf_muni")
assert ev is not None
dt = datetime.strptime(ev['starts_at'], "%Y-%m-%d %H:%M:%S")
assert dt.weekday() == 2 # Wednesday

hallucinated = {
    'title': 'SP-PDF BEERPONG LIGA 10. SEZONA',
    'starts_at': '2026-11-16 19:30:00',
    'description': 'Stredy v Koma Lounge'
}
corrected = sanitize_extracted_event_date(hallucinated, 'STREDY 19:30', now=ref)
assert corrected['starts_at'] == '2026-11-18 19:30:00'
print("OK")
PY;

        $proc = \Illuminate\Support\Facades\Process::path(base_path())
            ->env(['PYTHONPATH' => base_path()])
            ->run([$pythonBin, '-c', $script]);

        $this->assertTrue($proc->successful(), $proc->errorOutput() ?: $proc->output());
        $this->assertStringContainsString('OK', $proc->output());
    }

    public function test_sppdf_beerpong_event_resolves_to_wednesday(): void
    {
        $event = Event::create([
            'title' => 'SP-PDF BEERPONG LIGA 10. SEZÓNA',
            'slug' => 'sp-pdf-beerpong-test-' . uniqid(),
            'starts_at' => Carbon::parse('2026-11-18 19:30:00'),
            'source_url' => 'https://www.instagram.com/sppdf_muni/#post-d69dc02efbac',
            'status' => 'published',
        ]);

        $this->assertEquals(Carbon::WEDNESDAY, $event->starts_at->dayOfWeek);
        $this->assertEquals('2026-11-18 19:30:00', $event->starts_at->format('Y-m-d H:i:s'));
    }
}
