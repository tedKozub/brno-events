<?php

namespace App\Livewire;

use App\Models\Event;
use Livewire\Component;

class EventDetail extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function downloadIcal()
    {
        $event = Event::with('venue', 'organization')->where('slug', $this->slug)->firstOrFail();

        $start = $event->starts_at->format('Ymd\THis');
        $end = ($event->ends_at ?: $event->starts_at->copy()->addHours(3))->format('Ymd\THis');
        $title = addcslashes($event->title, ",;\\");
        $desc = addcslashes($event->short_description ?: $event->description ?: '', ",;\\");
        $loc = addcslashes($event->location_display, ",;\\");

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//Studentske Akce Brno//CZ\r\n";
        $ics .= "CALSCALE:GREGORIAN\r\n";
        $ics .= "METHOD:PUBLISH\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:event-{$event->id}@brnoakce.cz\r\n";
        $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:{$start}\r\n";
        $ics .= "DTEND:{$end}\r\n";
        $ics .= "SUMMARY:{$title}\r\n";
        $ics .= "DESCRIPTION:{$desc}\r\n";
        $ics .= "LOCATION:{$loc}\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return response()->streamDownload(function () use ($ics) {
            echo $ics;
        }, $event->slug . '.ics', [
            'Content-Type' => 'text/calendar; charset=utf-8',
        ]);
    }

    public function render()
    {
        $event = Event::with(['organization', 'venue', 'category'])
            ->where('slug', $this->slug)
            ->firstOrFail();

        // Increment views once per mount
        $event->increment('views_count');

        // Related upcoming events (same university or category)
        $relatedEvents = Event::with(['organization', 'venue', 'category'])
            ->published()
            ->upcoming()
            ->where('id', '!=', $event->id)
            ->where(function ($q) use ($event) {
                if ($event->organization_id) {
                    $q->where('organization_id', $event->organization_id)
                      ->orWhereHas('organization', fn ($oq) => $oq->where('university', $event->organization?->university));
                }
                if ($event->category_id) {
                    $q->orWhere('category_id', $event->category_id);
                }
            })
            ->orderBy('starts_at', 'asc')
            ->take(3)
            ->get();

        return view('livewire.event-detail', [
            'event' => $event,
            'relatedEvents' => $relatedEvents,
        ])->layout('components.layouts.app', [
            'title' => $event->title . ' | Studentské akce Brno',
            'description' => $event->short_description,
        ]);
    }
}
