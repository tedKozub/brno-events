<?php

namespace Tests\Feature;

use App\Livewire\EventBoard;
use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\ScrapeSource;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class ScraperVenueAndOrganizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_external_scraped_event_belongs_to_spolek_organization(): void
    {
        // 1. Spolek organization exists with type = spolek
        $spolek = Organization::firstOrCreate(
            ['slug' => 'sppdf-muni'],
            [
                'name' => 'SPPdF MUNI – Spolek posluchačů PdF MU',
                'type' => 'spolek',
                'university' => 'MUNI',
                'instagram_handle' => 'sppdf_muni',
                'is_active' => true,
            ]
        );

        $this->assertEquals('spolek', $spolek->type);

        // 2. Event scraped from @sppdf_muni must belong to the spolek, NOT a generic faculty or university
        $event = Event::create([
            'title' => 'SPPdF Kvízový večer v Koma Lounge',
            'slug' => 'sppdf-kvizovy-vecer-koma-test',
            'starts_at' => Carbon::tomorrow()->setTime(19, 0),
            'status' => 'published',
            'organization_id' => $spolek->id,
            'location_name' => 'Koma Lounge & Bar, Kounicova 42, Brno',
        ]);

        $this->assertEquals($spolek->id, $event->organization_id);
        $this->assertEquals('spolek', $event->organization->type);
        $this->assertNotEquals('muni-pdf', $event->organization->slug);
    }

    public function test_event_with_custom_venue_or_location_name_never_falls_back_to_brno_centrum(): void
    {
        // Case A: Event with linked Venue
        $venue = Venue::firstOrCreate(
            ['slug' => 'skautsky-institut-brno'],
            [
                'name' => 'Skautský institut v Brně',
                'address' => 'Moravské náměstí 12',
                'city' => 'Brno',
                'latitude' => 49.1983,
                'longitude' => 16.6075,
            ]
        );

        $eventWithVenue = Event::create([
            'title' => 'Debata v institutu',
            'slug' => 'debata-v-institutu-test',
            'starts_at' => Carbon::tomorrow()->setTime(18, 0),
            'status' => 'published',
            'venue_id' => $venue->id,
        ]);

        $this->assertStringContainsString('Skautský institut v Brně', $eventWithVenue->location_display);
        $coordsA = $eventWithVenue->map_coordinates;
        $this->assertNotNull($coordsA);
        $this->assertEquals('Skautský institut v Brně', $coordsA['title']);
        $this->assertStringNotContainsString('Brno centrum', $coordsA['title']);
        $this->assertTrue($coordsA['is_exact']);

        // Case B: Event without venue relation, but with custom location_name
        $eventWithLocName = Event::create([
            'title' => 'Akce v Koma Lounge',
            'slug' => 'akce-v-koma-lounge-test',
            'starts_at' => Carbon::tomorrow()->setTime(20, 0),
            'status' => 'published',
            'location_name' => 'Koma Lounge & Bar, Kounicova 42, Brno',
        ]);

        $this->assertEquals('Koma Lounge & Bar, Kounicova 42, Brno', $eventWithLocName->location_display);
        $coordsB = $eventWithLocName->map_coordinates;
        $this->assertNotNull($coordsB);
        $this->assertStringNotContainsString('Brno centrum', $coordsB['title']);
        $this->assertStringNotContainsString('Náměstí Svobody', $coordsB['address']);

        // Case C: Event with street location in text
        $eventWithStreet = Event::create([
            'title' => 'Promítání na Sukově',
            'slug' => 'promitani-na-sukove-test',
            'starts_at' => Carbon::tomorrow()->setTime(20, 0),
            'status' => 'published',
            'location_name' => 'Sukova 4, Brno',
        ]);

        $coordsC = $eventWithStreet->map_coordinates;
        $this->assertNotNull($coordsC);
        $this->assertStringNotContainsString('Brno centrum', $coordsC['title']);
    }

    public function test_event_board_map_markers_render_custom_venue_instead_of_brno_centrum(): void
    {
        $category = Category::firstOrCreate(['slug' => 'kultura'], ['name' => 'Kultura & Umění', 'icon' => '🎭', 'sort_order' => 1]);

        $event = Event::create([
            'title' => 'PUB QUIZZ v Koma Lounge Bar',
            'slug' => 'pub-quizz-koma-test-map',
            'starts_at' => Carbon::tomorrow()->setTime(19, 0),
            'status' => 'published',
            'category_id' => $category->id,
            'location_name' => 'Koma Lounge & Bar, Kounicova 42, Brno',
        ]);

        $component = Livewire::test(EventBoard::class, ['viewMode' => 'map']);

        $markers = $component->get('mapMarkers');
        $this->assertNotEmpty($markers);

        $matchingMarker = collect($markers)->firstWhere('id', $event->id);
        $this->assertNotNull($matchingMarker);
        $this->assertEquals('Koma Lounge & Bar, Kounicova 42, Brno', $matchingMarker['venue']);
        $this->assertNotEquals('Brno centrum', $matchingMarker['venue']);
    }

    public function test_instagram_parser_extracts_known_venues_and_addresses(): void
    {
        $pythonBin = base_path('scrapers/.venv/bin/python');
        if (!file_exists($pythonBin)) {
            $pythonBin = 'python3';
        }

        $script = <<<'PY'
from scrapers.parsers.instagram_parser import extract_event_from_caption

# Test 1: Caption mentioning Koma Lounge & Bar
caption1 = """Znovu přichází Váš oblíbený PUB QUIZZ!
Přijďte své fantastické znalosti otestovat 10.11.2026 v 19:00 do Brněnského Koma Lounge & Baru.
Startovné je 80 Kč za hráče. Těšíme se na Vás!"""

ev1 = extract_event_from_caption(caption1, "https://instagram.com/sppdf_muni")
assert ev1 is not None, "Failed to extract event from Koma Lounge caption"
assert "Koma Lounge & Bar" in ev1["location_name"], f"Expected Koma Lounge & Bar in location_name, got {ev1['location_name']}"
assert "Kounicova 42" in ev1["location_name"], f"Expected Kounicova 42 in location_name, got {ev1['location_name']}"

# Test 2: Caption mentioning Skautský institut
caption2 = """Přednáška o udržitelnosti!
Kdy: 12.11.2026 v 18:00
Kde: Skautský institut v Brně
Vstup volný!"""

ev2 = extract_event_from_caption(caption2, "https://instagram.com/skautsky_institut")
assert ev2 is not None, "Failed to extract event from Skautský institut caption"
assert "Skautský institut v Brně" in ev2["location_name"], f"Expected Skautský institut in location_name, got {ev2['location_name']}"

print("OK")
PY;

        $process = proc_open(
            [$pythonBin, '-c', $script],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            base_path()
        );

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertEquals(0, $exitCode, "Python parser test failed:\nSTDOUT: {$stdout}\nSTDERR: {$stderr}");
        $this->assertStringContainsString('OK', $stdout);
    }

    public function test_mendelu_scraper_matches_lednice_venue_and_avoids_zemedelska_fallback(): void
    {
        $ledniceVenue = Venue::firstOrCreate(
            ['slug' => 'zf-mendelu-lednice'],
            [
                'name' => 'ZF MENDELU',
                'address' => 'Valtická 337',
                'city' => 'Lednice',
                'latitude' => 48.7997,
                'longitude' => 16.8043,
            ]
        );

        $event = Event::create([
            'title' => 'Trendy v zahradnictví a krajinářské architektuře 2026',
            'slug' => 'trendy-v-zahradnictvi-test',
            'starts_at' => Carbon::parse('2026-11-24 09:00:00'),
            'ends_at' => Carbon::parse('2026-11-24 15:00:00'),
            'status' => 'published',
            'venue_id' => $ledniceVenue->id,
            'location_name' => 'ZF MENDELU, Valtická 337, Lednice',
        ]);

        $this->assertEquals('ZF MENDELU, Valtická 337', $event->location_display);
        $this->assertEquals('Lednice', $event->venue->city);
        $this->assertStringNotContainsString('Zemědělská 1, Brno', $event->location_display);
        $this->assertEquals('09:00', $event->starts_at->format('H:i'));
        $this->assertEquals('15:00', $event->ends_at->format('H:i'));
    }
}
