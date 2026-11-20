<?php

namespace App\Livewire;

use App\Models\Event;
use App\Models\EventReport;
use App\Services\DiscordNotifier;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ReportEventModal extends Component
{
    #[Locked]
    public int $eventId;

    public bool $isOpen = false;
    public string $reason = 'cancelled';
    public string $details = '';
    public string $contactEmail = '';
    public string $honeypot = '';
    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(array_keys(EventReport::$reasons))],
            'details' => ['nullable', 'string', 'max:1000'],
            'contactEmail' => ['nullable', 'email', 'max:150'],
        ];
    }

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
        if (!empty($this->honeypot)) {
            $this->submitted = true;
            return;
        }

        $ip = request()?->ip() ?? '127.0.0.1';
        $rateLimitKey = 'report-event:' . $ip;

        if (!app()->environment('testing') && RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('reason', "Příliš mnoho hlášení. Zkus to prosím znovu za {$seconds} sekund.");
            return;
        }

        $this->validate();

        $eventExists = Event::where('id', $this->eventId)->exists();
        if (!$eventExists) {
            $this->addError('reason', 'Akce nebyla nalezena.');
            return;
        }

        $report = EventReport::create([
            'event_id' => $this->eventId,
            'reason' => $this->reason,
            'details' => trim($this->details) ?: null,
            'contact_email' => trim($this->contactEmail) ?: null,
            'status' => 'pending',
        ]);

        RateLimiter::hit($rateLimitKey, 3600);

        if (!app()->environment('testing')) {
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
