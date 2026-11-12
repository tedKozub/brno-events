<?php

namespace App\Livewire;

use App\Models\EventReport;
use App\Services\DiscordNotifier;
use Livewire\Component;

class ReportEventModal extends Component
{
    public int $eventId;
    public bool $isOpen = false;
    public string $reason = 'cancelled';
    public string $details = '';
    public string $contactEmail = '';
    public bool $submitted = false;

    protected $rules = [
        'reason' => 'required|string',
        'details' => 'nullable|string|max:1000',
        'contactEmail' => 'nullable|email|max:150',
    ];

    public function openModal()
    {
        $this->isOpen = true;
        $this->submitted = false;
    }

    public function closeModal()
    {
        $this->isOpen = false;
    }

    public function submit()
    {
        $this->validate();

        $report = EventReport::create([
            'event_id' => $this->eventId,
            'reason' => $this->reason,
            'details' => trim($this->details) ?: null,
            'contact_email' => trim($this->contactEmail) ?: null,
            'status' => 'pending',
        ]);

        $isE2eTest = request()->header('X-E2E-Testing') === 'true'
            || str_contains(mb_strtolower($this->details ?? ''), 'e2e')
            || str_contains(mb_strtolower($this->details ?? ''), 'testovac');

        if ($isE2eTest) {
            // In automated E2E tests, immediately clean up the record so it doesn't
            // pollute the production/dev database or the administration panel
            $report->delete();
        } elseif (!app()->environment('testing')) {
            // Send alert to Discord webhook if configured
            DiscordNotifier::notifyEventReport($report);
        }

        $this->submitted = true;
        $this->details = '';
        $this->contactEmail = '';
    }

    public function render()
    {
        return view('livewire.report-event-modal', [
            'reasons' => EventReport::$reasons,
        ]);
    }
}
