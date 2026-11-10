<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class EventBoard extends Component
{
    public string $search = '';
    public string $dateFilter = 'all'; // all, today, tomorrow, this_week, next_week
    public string $selectedCategory = '';
    public string $selectedUniversity = '';
    public bool $onlyFree = false;
    public string $viewMode = 'cards'; // cards, map
    public ?int $selectedEventId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFilter' => ['except' => 'all'],
        'selectedCategory' => ['except' => ''],
        'selectedUniversity' => ['except' => ''],
        'onlyFree' => ['except' => false],
        'viewMode' => ['except' => 'cards'],
    ];

    public function setDateFilter(string $filter): void
    {
        $this->dateFilter = $filter;
    }

    public function setCategory(string $categorySlug): void
    {
        $this->selectedCategory = $this->selectedCategory === $categorySlug ? '' : $categorySlug;
    }

    public function setUniversity(string $university): void
    {
        $this->selectedUniversity = $this->selectedUniversity === $university ? '' : $university;
    }

    public function toggleFree(): void
    {
        $this->onlyFree = !$this->onlyFree;
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        if ($mode === 'map') {
            $this->dispatch('initMap');
        }
    }

    public function openEventModal(int $id): void
    {
        $this->selectedEventId = $id;
        $event = Event::find($id);
        if ($event) {
            $event->increment('views_count');
        }
    }

    public function closeEventModal(): void
    {
        $this->selectedEventId = null;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->dateFilter = 'all';
        $this->selectedCategory = '';
        $this->selectedUniversity = '';
        $this->onlyFree = false;
    }

    public function downloadIcal(int $id)
    {
        $event = Event::with('venue', 'organization')->findOrFail($id);

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
        $now = Carbon::now();

        $query = Event::with(['organization', 'venue', 'category'])
            ->published()
            ->upcoming();

        // Fulltext vyhledávání
        if (!empty($this->search)) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('short_description', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('location_name', 'like', $term)
                  ->orWhereHas('venue', fn ($vq) => $vq->where('name', 'like', $term)->orWhere('address', 'like', $term))
                  ->orWhereHas('organization', fn ($oq) => $oq->where('name', 'like', $term));
            });
        }

        // Časové filtry pro studenty (bez víkendů)
        match ($this->dateFilter) {
            'today' => $query->whereDate('starts_at', Carbon::today()),
            'tomorrow' => $query->whereDate('starts_at', Carbon::tomorrow()),
            'this_week' => $query->whereBetween('starts_at', [
                Carbon::today()->startOfDay(),
                $now->copy()->endOfWeek(Carbon::FRIDAY)->setTime(23, 59, 59)
            ]),
            'next_week' => $query->whereBetween('starts_at', [
                $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->startOfDay(),
                $now->copy()->addWeek()->endOfWeek(Carbon::FRIDAY)->setTime(23, 59, 59)
            ]),
            default => null,
        };

        // Filtr kategorie
        if (!empty($this->selectedCategory)) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $this->selectedCategory));
        }

        // Filtr univerzity
        if (!empty($this->selectedUniversity)) {
            $query->whereHas('organization', fn ($q) => $q->where('university', $this->selectedUniversity));
        }

        // Filtr zdarma
        if ($this->onlyFree) {
            $query->where('price_type', 'free');
        }

        $events = $query->orderBy('starts_at', 'asc')->get();

        // Data pro Leaflet mapu (místa se souřadnicemi v Brně)
        $mapMarkers = $events->filter(fn ($e) => $e->venue && $e->venue->latitude && $e->venue->longitude)
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'title' => $e->title,
                    'lat' => (float) $e->venue->latitude,
                    'lng' => (float) $e->venue->longitude,
                    'venue' => $e->venue->name,
                    'time' => $e->starts_at->translatedFormat('D d. m. H:i'),
                    'price' => $e->formatted_price,
                    'category' => $e->category?->name,
                ];
            })->values()->toArray();

        $categories = Category::withCount(['events' => fn ($q) => $q->published()->upcoming()])
            ->orderBy('sort_order', 'asc')
            ->get();

        $selectedEvent = $this->selectedEventId ? Event::with(['organization', 'venue', 'category'])->find($this->selectedEventId) : null;

        return view('livewire.event-board', [
            'events' => $events,
            'categories' => $categories,
            'mapMarkers' => $mapMarkers,
            'selectedEvent' => $selectedEvent,
            'totalUpcomingCount' => Event::published()->upcoming()->count(),
        ]);
    }
}
