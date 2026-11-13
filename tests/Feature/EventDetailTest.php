<?php

namespace Tests\Feature;

use App\Models\Event;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EventDetailTest extends TestCase
{
    use DatabaseTransactions;

    public function test_event_detail_page_renders_successfully(): void
    {
        $event = Event::published()->first();

        $this->assertNotNull($event, 'At least one published event must exist for testing');

        $response = $this->get('/akce/' . $event->slug);

        $response->assertStatus(200);
        $response->assertSee($event->title);
        $response->assertSee('Zpět na přehled všech akcí');
    }

    public function test_event_detail_ical_download(): void
    {
        $event = Event::published()->first();

        $this->assertNotNull($event, 'At least one published event must exist for testing');

        Livewire::test(\App\Livewire\EventDetail::class, ['slug' => $event->slug])
            ->call('downloadIcal')
            ->assertFileDownloaded($event->slug . '.ics');
    }

    public function test_past_event_displays_archive_alert_banner(): void
    {
        $event = Event::published()->first();
        $pastEvent = $event->replicate();
        $pastEvent->title = 'Proběhlá minulá akce ' . uniqid();
        $pastEvent->slug = 'past-event-' . uniqid();
        $pastEvent->starts_at = now()->subDays(10);
        $pastEvent->ends_at = now()->subDays(10)->addHours(2);
        $pastEvent->ticket_url = 'https://example.com/tickets/past';
        $pastEvent->save();

        $response = $this->get('/akce/' . $pastEvent->slug);

        $response->assertStatus(200);
        $response->assertSee('Tato akce již proběhla');
        $response->assertSee('Informace slouží jako archiv');
        $response->assertSee('Vstupenky (archiv)');
    }
}
