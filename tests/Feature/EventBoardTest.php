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
        $event = Event::published()->upcoming()->first();

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('AKCE');
        $response->assertSee('BRNO');
        if ($event) {
            $response->assertSee(e($event->title), false);
        }
    }

    public function test_livewire_can_filter_events(): void
    {
        Livewire::test(\App\Livewire\EventBoard::class)
            ->set('search', 'Beerpong')
            ->assertSee('Beerpong')
            ->assertDontSee('Velký studentský hospodský kvíz')
            ->set('search', '')
            ->set('selectedUniversity', 'MUNI')
            ->assertSee('MUNI');
    }

    public function test_category_counts_dynamically_reflect_active_filters(): void
    {
        $component = Livewire::test(\App\Livewire\EventBoard::class);
        $allCount = $component->viewData('allCategoriesCount');
        $this->assertIsInt($allCount);

        // When filtering by a search term that has few or 1 result
        $searchComponent = Livewire::test(\App\Livewire\EventBoard::class)
            ->set('search', 'Beerpong');
        $filteredAllCount = $searchComponent->viewData('allCategoriesCount');
        $this->assertLessThanOrEqual($allCount, $filteredAllCount);
    }

    public function test_semantic_search_finds_relevant_events(): void
    {
        // Dotaz "hudba" by měl najít hudební akce jako Jam & Acoustic Night nebo Fléda Mecheche
        Livewire::test(\App\Livewire\EventBoard::class)
            ->set('search', 'hudba')
            ->assertSee('sémantické hledání')
            ->assertSee('Jam');
    }

    public function test_livewire_can_open_and_close_event_modal(): void
    {
        $event = Event::published()->upcoming()->first();

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

    public function test_dynamic_pagination_and_load_more(): void
    {
        $component = Livewire::test(\App\Livewire\EventBoard::class);

        $events = $component->viewData('events');
        $totalCount = $component->viewData('totalEventsCount');
        $hasMore = $component->viewData('hasMore');

        $this->assertLessThanOrEqual(12, $events->count());
        if ($totalCount > 12) {
            $this->assertTrue($hasMore);
            $this->assertEquals(12, $events->count());

            // Calling loadMore increases perPage and loads more events
            $component->call('loadMore');
            $updatedEvents = $component->viewData('events');
            $this->assertGreaterThan(12, $updatedEvents->count());
        }

        // Filtering resets perPage
        $component->call('setDateFilter', 'today');
        $this->assertEquals(12, $component->get('perPage'));
    }
}
