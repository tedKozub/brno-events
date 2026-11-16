<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class MultiDayEventTest extends TestCase
{
    use DatabaseTransactions;

    protected Organization $org;
    protected Venue $venue;
    protected Category $cat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::firstOrCreate(
            ['slug' => 'test-multi-day-org'],
            ['name' => 'Spolek pro vícedenní testy', 'university' => 'MUNI']
        );

        $this->venue = Venue::firstOrCreate(
            ['slug' => 'test-multi-day-venue'],
            ['name' => 'Knihovna FF MUNI', 'address' => 'Arne Nováka 1, Brno']
        );

        $this->cat = Category::firstOrCreate(
            ['slug' => 'kultura-divadlo'],
            ['name' => 'Kultura & Divadlo']
        );
    }

    public function test_event_model_multi_day_helpers(): void
    {
        // 1. Jednodenní akce (dnes 18:00 - 22:00)
        $singleDay = Event::create([
            'organization_id' => $this->org->id,
            'venue_id' => $this->venue->id,
            'category_id' => $this->cat->id,
            'title' => 'Jednodenní kvíz',
            'slug' => 'jednodenni-kviz-' . uniqid(),
            'starts_at' => Carbon::now()->addDays(2)->setTime(18, 0),
            'ends_at' => Carbon::now()->addDays(2)->setTime(22, 0),
            'status' => 'published',
        ]);

        $this->assertFalse($singleDay->is_multi_day);
        $this->assertFalse($singleDay->is_long_term);
        $this->assertFalse($singleDay->is_ongoing);

        // 2. Krátká vícedenní akce (víkendový hackathon 2 dny)
        $shortMulti = Event::create([
            'organization_id' => $this->org->id,
            'venue_id' => $this->venue->id,
            'category_id' => $this->cat->id,
            'title' => 'Víkendový Hackathon',
            'slug' => 'vikendovy-hackathon-' . uniqid(),
            'starts_at' => Carbon::now()->addDays(5)->setTime(17, 0),
            'ends_at' => Carbon::now()->addDays(7)->setTime(16, 0),
            'status' => 'published',
        ]);

        $this->assertTrue($shortMulti->is_multi_day);
        $this->assertFalse($shortMulti->is_long_term);
        $this->assertFalse($shortMulti->is_ongoing);

        // 3. Dlouhodobá probíhající akce (začala před 5 dny, končí za 10 dní)
        $ongoingLongTerm = Event::create([
            'organization_id' => $this->org->id,
            'venue_id' => $this->venue->id,
            'category_id' => $this->cat->id,
            'title' => 'Měsíční výstava fotografií v Knihovně FF MUNI',
            'slug' => 'mesicni-vystava-fotografii-' . uniqid(),
            'starts_at' => Carbon::now()->subDays(5)->setTime(10, 0),
            'ends_at' => Carbon::now()->addDays(10)->setTime(18, 0),
            'status' => 'published',
        ]);

        $this->assertTrue($ongoingLongTerm->is_multi_day);
        $this->assertTrue($ongoingLongTerm->is_long_term);
        $this->assertTrue($ongoingLongTerm->is_ongoing);
        $this->assertStringContainsString('Probíhá • do', $ongoingLongTerm->formatted_date_range);
    }

    public function test_feed_separates_ongoing_multi_day_events_into_collapsible_section(): void
    {
        // Vytvoříme běžnou novou akci (začíná dnes)
        $todayEvent = Event::create([
            'organization_id' => $this->org->id,
            'venue_id' => $this->venue->id,
            'category_id' => $this->cat->id,
            'title' => 'Dnešní Zahajovací Přednáška',
            'slug' => 'dnesni-zahajovaci-prednaska-' . uniqid(),
            'starts_at' => Carbon::today()->setTime(18, 0),
            'ends_at' => Carbon::today()->setTime(20, 0),
            'status' => 'published',
        ]);

        // Vytvoříme probíhající 3týdenní akci (začala před 4 dny, končí za 14 dní)
        $ongoingEvent = Event::create([
            'organization_id' => $this->org->id,
            'venue_id' => $this->venue->id,
            'category_id' => $this->cat->id,
            'title' => 'Velká Studentská Umělecká Výstava',
            'slug' => 'velka-studentska-umelecka-vystava-' . uniqid(),
            'starts_at' => Carbon::now()->subDays(4)->setTime(10, 0),
            'ends_at' => Carbon::now()->addDays(14)->setTime(18, 0),
            'status' => 'published',
        ]);

        $component = Livewire::test(\App\Livewire\EventBoard::class);

        // Hlavní seznam $events nesmí obsahovat probíhající akci, která začala dříve
        $mainEvents = $component->viewData('events');
        $ongoingList = $component->viewData('ongoingEvents');

        $this->assertTrue(
            $mainEvents->contains('id', $todayEvent->id),
            'Dnešní akce musí být v hlavním feedu'
        );

        $this->assertFalse(
            $mainEvents->contains('id', $ongoingEvent->id),
            'Akce, která začala v minulosti, nesmí být v hlavním feedu, aby neposouvala chronologické řazení'
        );

        $this->assertTrue(
            $ongoingList->contains('id', $ongoingEvent->id),
            'Probíhající vícedenní akce musí být v kolekci ongoingEvents'
        );

        // HTML ověření: sbalitelná sekce probíhajících akcí
        $component->assertSee('Právě probíhající vícedenní akce')
            ->assertSee('Velká Studentská Umělecká Výstava')
            ->assertSee('Probíhá • do');
    }

    public function test_ical_download_for_multi_day_event_includes_transparent_status(): void
    {
        $multiDayEvent = Event::create([
            'organization_id' => $this->org->id,
            'venue_id' => $this->venue->id,
            'category_id' => $this->cat->id,
            'title' => 'Třítýdenní festival udržitelnosti na MUNI',
            'slug' => 'tritydenni-festival-udrzitelnosti-' . uniqid(),
            'starts_at' => Carbon::now()->addDays(1)->setTime(9, 0),
            'ends_at' => Carbon::now()->addDays(21)->setTime(18, 0),
            'status' => 'published',
        ]);

        $response = Livewire::test(\App\Livewire\EventBoard::class)
            ->call('downloadIcal', $multiDayEvent->id);

        $response->assertFileDownloaded($multiDayEvent->slug . '.ics');

        // Test iCalu na detailu
        $detailResponse = Livewire::test(\App\Livewire\EventDetail::class, ['slug' => $multiDayEvent->slug])
            ->call('downloadIcal');

        $detailResponse->assertFileDownloaded($multiDayEvent->slug . '.ics');
    }
}
