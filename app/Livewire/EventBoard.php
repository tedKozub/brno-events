<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Event;
use App\Models\Organization;
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
    public array $mapMarkers = [];

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

        // Základní dotaz pro události dle filtrů (datum, univerzita, cena zdarma)
        $baseQuery = Event::with(['organization', 'venue', 'category'])
            ->published()
            ->upcoming();

        // Časové filtry pro studenty (bez víkendů)
        match ($this->dateFilter) {
            'today' => $baseQuery->whereDate('starts_at', Carbon::today()),
            'tomorrow' => $baseQuery->whereDate('starts_at', Carbon::tomorrow()),
            'this_week' => $baseQuery->whereBetween('starts_at', [
                Carbon::today()->startOfDay(),
                $now->copy()->endOfWeek(Carbon::FRIDAY)->setTime(23, 59, 59)
            ]),
            'next_week' => $baseQuery->whereBetween('starts_at', [
                $now->copy()->addWeek()->startOfWeek(Carbon::MONDAY)->startOfDay(),
                $now->copy()->addWeek()->endOfWeek(Carbon::FRIDAY)->setTime(23, 59, 59)
            ]),
            default => null,
        };

        // Filtr univerzity přes rychlý indexovaný IN dotaz namísto pomalého correlated whereHas
        if (!empty($this->selectedUniversity)) {
            $orgIds = Organization::where('university', $this->selectedUniversity)->pluck('id');
            $baseQuery->whereIn('organization_id', $orgIds);
        }

        // Filtr zdarma
        if ($this->onlyFree) {
            $baseQuery->where('price_type', 'free');
        }

        // Vyhledávání: Hybridní (vektorový sémantický embedding + fulltext keyword match)
        if (!empty($this->search)) {
            $searchTerm = trim($this->search);
            $normSearch = mb_strtolower($searchTerm);
            $keywords = array_filter(explode(' ', $normSearch), fn ($w) => mb_strlen($w) >= 2);

            $embeddingService = app(\App\Services\EmbeddingService::class);
            $queryVector = $embeddingService->getEmbedding($searchTerm);

            if ($queryVector) {
                // Načteme kandidáty dle ostatních filtrů bez kategorie
                $candidates = (clone $baseQuery)->get();
                $scored = [];

                foreach ($candidates as $event) {
                    $semanticSim = 0.0;
                    if (!empty($event->embedding) && is_array($event->embedding)) {
                        $semanticSim = \App\Services\EmbeddingService::cosineSimilarity($queryVector, $event->embedding);
                    }

                    // Klíčová slova – textové shody
                    $titleNorm = mb_strtolower($event->title ?? '');
                    $descNorm = mb_strtolower(($event->short_description ?? '') . ' ' . ($event->description ?? ''));
                    $locNorm = mb_strtolower(($event->location_name ?? '') . ' ' . ($event->venue?->name ?? '') . ' ' . ($event->organization?->name ?? ''));

                    $hasExactTitleMatch = str_contains($titleNorm, $normSearch);
                    $hasWordTitleMatch = false;
                    $hasWordBodyMatch = false;

                    foreach ($keywords as $kw) {
                        if (str_contains($titleNorm, $kw)) {
                            $hasWordTitleMatch = true;
                        }
                        if (str_contains($descNorm, $kw) || str_contains($locNorm, $kw)) {
                            $hasWordBodyMatch = true;
                        }
                    }

                    $keywordBonus = 0.0;
                    if ($hasExactTitleMatch) {
                        $keywordBonus += 0.8;
                    } elseif ($hasWordTitleMatch) {
                        $keywordBonus += 0.4;
                    }
                    if ($hasWordBodyMatch) {
                        $keywordBonus += 0.2;
                    }

                    $totalScore = $semanticSim + $keywordBonus;

                    // Zařazení: buď shoda klíčových slov nebo relevantní sémantická podobnost (>= 0.30)
                    if ($keywordBonus > 0 || $semanticSim >= 0.30) {
                        $scored[] = [
                            'event' => $event,
                            'score' => $totalScore,
                            'starts_at' => $event->starts_at?->timestamp ?? 0,
                        ];
                    }
                }

                // Seřazení podle relevance skóre sestupně, při těsné shodě dle času začátku
                usort($scored, function ($a, $b) {
                    if (abs($a['score'] - $b['score']) > 0.08) {
                        return $b['score'] <=> $a['score'];
                    }
                    return $a['starts_at'] <=> $b['starts_at'];
                });

                $eventsBeforeCategory = collect(array_column($scored, 'event'));
            } else {
                // Fallback na klasické SQL vyhledávání
                $term = '%' . $searchTerm . '%';
                $searchQuery = clone $baseQuery;
                $searchQuery->where(function (Builder $q) use ($term) {
                    $q->where('title', 'like', $term)
                      ->orWhere('short_description', 'like', $term)
                      ->orWhere('description', 'like', $term)
                      ->orWhere('location_name', 'like', $term)
                      ->orWhereHas('venue', fn ($vq) => $vq->where('name', 'like', $term)->orWhere('address', 'like', $term))
                      ->orWhereHas('organization', fn ($oq) => $oq->where('name', 'like', $term));
                });
                $eventsBeforeCategory = $searchQuery->orderBy('starts_at', 'asc')->get();
            }

            // Spočítat reálné počty kategorií z nalezených položek
            $categoryCounts = $eventsBeforeCategory
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->map(fn ($group) => $group->count())
                ->toArray();
            $allCategoriesCount = $eventsBeforeCategory->count();

            // Aplikovat filtr vybrané kategorie
            if (!empty($this->selectedCategory)) {
                $events = $eventsBeforeCategory->filter(fn ($e) => $e->category?->slug === $this->selectedCategory)->values();
            } else {
                $events = $eventsBeforeCategory->values();
            }
        } else {
            // Bez vyhledávání: spočítat přesné počty položek pro každou kategorii odpovídající aktivním filtrům
            $categoryCounts = (clone $baseQuery)
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->selectRaw('category_id, count(*) as count')
                ->pluck('count', 'category_id')
                ->toArray();
            $allCategoriesCount = (clone $baseQuery)->count();

            // Aplikovat filtr kategorie na výsledky (přímý indexovaný category_id namísto whereHas)
            $query = clone $baseQuery;
            if (!empty($this->selectedCategory)) {
                $catId = Category::where('slug', $this->selectedCategory)->value('id');
                if ($catId) {
                    $query->where('category_id', $catId);
                }
            }
            $events = $query->orderBy('starts_at', 'asc')->get();
        }

        // Optimalizace: Markery pro mapu počítáme POUZE v režimu mapy (ušetří 30-50ms CPU a 15KB JSON payloadu na každý klik)
        if ($this->viewMode === 'map') {
            $this->mapMarkers = $events->map(function ($e) {
                $coords = $e->map_coordinates;
                if (!$coords) {
                    return null;
                }
                return [
                    'id' => $e->id,
                    'slug' => $e->slug,
                    'title' => $e->title,
                    'lat' => (float) $coords['lat'],
                    'lng' => (float) $coords['lng'],
                    'venue' => $coords['title'],
                    'time' => $e->starts_at->translatedFormat('D d. m. H:i'),
                    'price' => $e->formatted_price,
                    'category' => $e->category?->name,
                ];
            })->filter()->values()->toArray();

            // Informovat front-end (Alpine / Leaflet) o aktualizovaných markerech
            $this->dispatch('markers-updated', markers: $this->mapMarkers);
        } else {
            $this->mapMarkers = [];
        }

        // Kategorie s přesným počtem reflektujícím aktivní filtry
        $categories = Category::orderBy('sort_order', 'asc')
            ->get()
            ->map(function ($cat) use ($categoryCounts) {
                $cat->events_count = $categoryCounts[$cat->id] ?? 0;
                return $cat;
            });

        $selectedEvent = $this->selectedEventId ? Event::with(['organization', 'venue', 'category'])->find($this->selectedEventId) : null;

        return view('livewire.event-board', [
            'events' => $events,
            'categories' => $categories,
            'allCategoriesCount' => $allCategoriesCount,
            'mapMarkers' => $this->mapMarkers,
            'selectedEvent' => $selectedEvent,
            'totalUpcomingCount' => Event::published()->upcoming()->count(),
        ])->layout('components.layouts.app', [
            'title' => 'Studentské Akce Brno | Průvodce studentským životem (MUNI, VUT, MENDELU)',
            'description' => 'Aktuální přehled studentských párty, festivalů, přednášek, turnajů a kulturních akcí v Brně. Interaktivní mapa, kalendář a vyhledávání.',
            'ogImage' => asset('images/og-brnoakce.jpg'),
            'ogType' => 'website',
            'ogUrl' => url('/'),
        ]);
    }
}
