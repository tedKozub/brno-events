<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
use App\Models\User;
use App\Models\Venue;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EventBoardTest extends TestCase
{
    use DatabaseTransactions;
    public function test_homepage_renders_with_events(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('AKCE');
        $response->assertSee('BRNO');
        $response->assertSee('Velký studentský hospodský kvíz');
    }

    public function test_livewire_can_filter_events(): void
    {
        Livewire::test(\App\Livewire\EventBoard::class)
            ->set('search', 'Beerpong')
            ->assertSee('Beerpong')
            ->assertDontSee('Velký studentský hospodský kvíz')
            ->set('search', '')
            ->set('selectedUniversity', 'MUNI')
            ->assertSee('MUNIE');
    }

    public function test_livewire_can_open_and_close_event_modal(): void
    {
        $event = Event::published()->first();

        Livewire::test(\App\Livewire\EventBoard::class)
            ->call('openEventModal', $event->id)
            ->assertSet('selectedEventId', $event->id)
            ->assertSee($event->title)
            ->call('closeEventModal')
            ->assertSet('selectedEventId', null);
    }

    public function test_ical_download(): void
    {
        $event = Event::published()->first();

        $response = Livewire::test(\App\Livewire\EventBoard::class)
            ->call('downloadIcal', $event->id);

        $response->assertFileDownloaded($event->slug . '.ics');
    }

    public function test_admin_panel_accessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }
}
