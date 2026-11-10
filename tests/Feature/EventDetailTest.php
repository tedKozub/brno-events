<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventDetailTest extends TestCase
{
    public function test_event_detail_page_renders_successfully(): void
    {
        $suffix = uniqid();
        $org = Organization::create([
            'name' => 'MUNIE Test ' . $suffix,
            'slug' => 'munie-test-' . $suffix,
            'university' => 'MUNI',
        ]);

        $venue = Venue::create([
            'name' => 'Fléda Test ' . $suffix,
            'slug' => 'fleda-test-' . $suffix,
            'latitude' => 49.2096,
            'longitude' => 16.6033,
        ]);

        $cat = Category::create([
            'name' => 'Párty Test ' . $suffix,
            'slug' => 'party-test-' . $suffix,
        ]);

        $event = Event::create([
            'title' => 'Unikátní Testovací Párty 2026',
            'slug' => 'unikatni-testovaci-party-' . $suffix,
            'organization_id' => $org->id,
            'venue_id' => $venue->id,
            'category_id' => $cat->id,
            'starts_at' => now()->addDays(2),
            'status' => 'published',
            'price_type' => 'free',
            'description' => 'Kompletní popis skvělé studentské akce.',
        ]);

        $response = $this->get('/akce/' . $event->slug);

        $response->assertStatus(200);
        $response->assertSee('Unikátní Testovací Párty 2026');
        $response->assertSee('MUNIE Test');
        $response->assertSee('Fléda Test');
        $response->assertSee('Zpět na akce');
    }

    public function test_event_detail_ical_download(): void
    {
        $event = Event::first();
        if (!$event) {
            $event = Event::create([
                'title' => 'Ical Test Event',
                'slug' => 'ical-test-event',
                'starts_at' => now()->addDays(1),
                'status' => 'published',
            ]);
        }

        Livewire::test(\App\Livewire\EventDetail::class, ['slug' => $event->slug])
            ->call('downloadIcal')
            ->assertFileDownloaded($event->slug . '.ics');
    }
}
