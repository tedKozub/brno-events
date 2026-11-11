<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventReport;
use App\Livewire\ReportEventModal;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class EventReportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_report_an_event_issue(): void
    {
        $event = Event::first();
        $this->assertNotNull($event);

        Livewire::test(ReportEventModal::class, ['eventId' => $event->id])
            ->set('reason', 'wrong_time')
            ->set('details', 'Akce byla presunuta na 20:00 misto 18:00')
            ->set('contactEmail', 'student@muni.cz')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('event_reports', [
            'event_id' => $event->id,
            'reason' => 'wrong_time',
            'contact_email' => 'student@muni.cz',
            'status' => 'pending',
        ]);
    }

    public function test_event_has_missing_fields_attribute(): void
    {
        $event = Event::first();
        $this->assertNotNull($event);
        $this->assertIsArray($event->missing_fields);
    }
}
