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
        $response->assertSee('Zpět na akce');
    }

    public function test_event_detail_ical_download(): void
    {
        $event = Event::published()->first();

        $this->assertNotNull($event, 'At least one published event must exist for testing');

        Livewire::test(\App\Livewire\EventDetail::class, ['slug' => $event->slug])
            ->call('downloadIcal')
            ->assertFileDownloaded($event->slug . '.ics');
    }
}
