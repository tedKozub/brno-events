<div>
    {{-- HLAVNÍ HLAVIČKA A NAVBAR --}}
    <header class="border-b border-base-300 bg-base-100/80 backdrop-blur-md sticky top-0 z-30 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between gap-4">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-primary flex items-center justify-center text-primary-content font-black shadow-md shadow-primary/20 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-black text-lg sm:text-xl tracking-tight text-base-content">
                            AKCE<span class="text-primary">BRNO</span>
                        </span>
                        <span class="badge badge-sm badge-outline font-bold text-[10px] uppercase tracking-wider text-primary border-primary">
                            Student
                        </span>
                    </div>
                    <p class="text-xs text-base-content/60 hidden sm:block">Centralizovaný přehled studentského života v Brně</p>
                </div>
            </a>

            {{-- Pravá část: Přepínač témat + Vstup pro spolky --}}
            <div class="flex items-center gap-2.5">
                {{-- Theme Switcher --}}
                <div class="join bg-base-200 p-1 rounded-xl border border-base-300">
                    <button
                        type="button"
                        onclick="switchAppTheme('events-light')"
                        id="theme-light-btn"
                        class="btn btn-xs join-item px-2.5 gap-1.5 font-bold"
                        title="Světlý režim"
                    >
                        <svg class="w-3.5 h-3.5 text-warning" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <span class="hidden md:inline">Světlý</span>
                    </button>
                    <button
                        type="button"
                        onclick="switchAppTheme('events-dark')"
                        id="theme-dark-btn"
                        class="btn btn-xs join-item px-2.5 gap-1.5 font-bold"
                        title="Tmavý režim"
                    >
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                        </svg>
                        <span class="hidden md:inline">Tmavý</span>
                    </button>
                </div>

                {{-- Odkaz do administrace pro spolky --}}
                <a
                    href="/admin"
                    class="btn btn-sm btn-primary gap-1.5 shadow-sm font-bold text-xs uppercase tracking-wider"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Portál spolků</span>
                </a>
            </div>
        </div>
    </header>

    {{-- HLAVNÍ OBSAHOVÁ OBLAST --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

        {{-- HERO FILTROVACÍ PANEL --}}
        <section class="card bg-base-100 shadow-md border border-base-300 overflow-hidden">
            <div class="card-body p-4 sm:p-6 space-y-4">
                {{-- 1. Vyhledávání a přepínač zobrazení --}}
                <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                    {{-- Search Input --}}
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Hledat akci, párty, turnaj, klub, spolek..."
                            class="input input-bordered w-full pl-11 bg-base-200/50 text-sm font-medium focus:border-primary focus:outline-none"
                        />
                        @if(!empty($search))
                            <button
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-base-content/40 hover:text-base-content"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Přepínač pohledů: Seznam vs Mapa --}}
                    <div class="join bg-base-200 p-1 rounded-xl border border-base-300 self-end md:self-auto w-full md:w-auto">
                        <button
                            wire:click="setViewMode('cards')"
                            class="btn btn-sm join-item flex-1 md:flex-initial gap-1.5 font-bold text-xs {{ $viewMode === 'cards' ? 'btn-primary' : 'btn-ghost' }}"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            <span>Přehled karet</span>
                        </button>
                        <button
                            wire:click="setViewMode('map')"
                            class="btn btn-sm join-item flex-1 md:flex-initial gap-1.5 font-bold text-xs {{ $viewMode === 'map' ? 'btn-primary' : 'btn-ghost' }}"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            <span>Mapa Brna</span>
                        </button>
                    </div>
                </div>

                {{-- 2. Časové filtry (Dnes / Zítra / Víkend...) --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 pt-1 scrollbar-none">
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 pr-1 shrink-0">Kdy:</span>
                    @php
                        $dateOptions = [
                            'all' => 'Všechny dny',
                            'today' => 'Dnes',
                            'tomorrow' => 'Zítra',
                            'weekend' => 'Tento víkend',
                            'week' => 'Příštích 7 dní',
                        ];
                    @endphp
                    @foreach($dateOptions as $key => $label)
                        <button
                            wire:click="setDateFilter('{{ $key }}')"
                            class="btn btn-xs sm:btn-sm shrink-0 rounded-lg font-bold transition-all {{ $dateFilter === $key ? 'btn-primary shadow-sm' : 'btn-outline border-base-300 hover:bg-base-200 text-base-content/80' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- 3. Univerzity a Vstupné --}}
                <div class="flex flex-wrap items-center justify-between gap-3 pt-2 border-t border-base-200">
                    {{-- Univerzity --}}
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 pr-1">Škola:</span>
                        @foreach(['' => 'Všechny', 'MUNI' => 'MUNI', 'VUT' => 'VUT', 'MENDELU' => 'MENDELU'] as $code => $uLabel)
                            <button
                                wire:click="setUniversity('{{ $code }}')"
                                class="btn btn-xs rounded-lg font-bold {{ $selectedUniversity === $code ? 'btn-neutral' : 'btn-ghost text-base-content/70' }}"
                            >
                                {{ $uLabel }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Vstupné zdarma toggle --}}
                    <div class="flex items-center gap-2">
                        <label class="cursor-pointer label gap-2 py-0">
                            <span class="label-text text-xs font-bold uppercase tracking-wider text-base-content/80">Pouze akce zdarma</span>
                            <input
                                type="checkbox"
                                wire:click="toggleFree"
                                {{ $onlyFree ? 'checked' : '' }}
                                class="toggle toggle-primary toggle-sm"
                            />
                        </label>
                    </div>
                </div>

                {{-- 4. Kategorie chips --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none pt-1">
                    <button
                        wire:click="setCategory('')"
                        class="badge badge-lg gap-1.5 font-bold cursor-pointer transition-colors {{ empty($selectedCategory) ? 'badge-primary text-primary-content' : 'badge-ghost border-base-300' }}"
                    >
                        Všechny kategorie
                    </button>
                    @foreach($categories as $cat)
                        <button
                            wire:click="setCategory('{{ $cat->slug }}')"
                            class="badge badge-lg gap-1.5 font-bold cursor-pointer transition-colors {{ $selectedCategory === $cat->slug ? 'badge-primary text-primary-content' : 'badge-ghost border-base-300' }}"
                        >
                            <span>{{ $cat->name }}</span>
                            @if($cat->events_count > 0)
                                <span class="opacity-70 text-[10px]">({{ $cat->events_count }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- STATUSOVÁ LIŠTA S POČTEM VÝSLEDKŮ --}}
        <div class="flex items-center justify-between px-1 text-xs text-base-content/70">
            <div>
                Zobrazeno <strong class="text-base-content">{{ $events->count() }}</strong> z celkem {{ $totalUpcomingCount }} nadcházejících akcí
                @if(!empty($search) || !empty($selectedCategory) || !empty($selectedUniversity) || $dateFilter !== 'all' || $onlyFree)
                    <span class="text-primary font-bold">(aktivní filtry)</span>
                @endif
            </div>

            @if(!empty($search) || !empty($selectedCategory) || !empty($selectedUniversity) || $dateFilter !== 'all' || $onlyFree)
                <button
                    wire:click="resetFilters"
                    class="btn btn-ghost btn-xs text-error font-bold gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Zrušit všechny filtry
                </button>
            @endif
        </div>

        {{-- ZOBRAZENÍ: MAPA BRNA --}}
        @if($viewMode === 'map')
            <section class="card bg-base-100 shadow-md border border-base-300 overflow-hidden">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between pb-2">
                        <h2 class="text-base font-bold flex items-center gap-2">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Interaktivní mapa míst a klubů v Brně
                        </h2>
                        <span class="text-xs text-base-content/60">{{ $mapMarkers->count() }} míst se souřadnicemi</span>
                    </div>

                    <div
                        id="brno-events-map"
                        wire:ignore
                        class="w-full h-[500px] rounded-xl bg-base-200 border border-base-300 z-10"
                    ></div>
                </div>
            </section>
        @endif

        {{-- ZOBRAZENÍ: SEZNAM KARET --}}
        @if($viewMode === 'cards')
            @if($events->isEmpty())
                <div class="card bg-base-100 border border-base-300 p-12 text-center">
                    <div class="max-w-md mx-auto space-y-3">
                        <div class="w-12 h-12 rounded-full bg-base-200 mx-auto flex items-center justify-center text-base-content/40">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold">Žádná akce neodpovídá zvoleným filtrům</h3>
                        <p class="text-xs text-base-content/70">Zkuste rozšířit časové rozmezí nebo vyčistit hledaný výraz.</p>
                        <button wire:click="resetFilters" class="btn btn-primary btn-sm mt-2 font-bold">
                            Zobrazit všechny nadcházející akce
                        </button>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($events as $event)
                        <div class="card bg-base-100 shadow-sm hover:shadow-lg border border-base-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                            {{-- Horní část: Obrázek & Odznaky --}}
                            <div class="relative aspect-[16/9] w-full bg-base-300 overflow-hidden">
                                <img
                                    src="{{ $event->cover_image ?: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=800&q=80' }}"
                                    alt="{{ $event->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />

                                {{-- Odznaky přes obrázek --}}
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                    @if($event->category)
                                        <span class="badge badge-sm badge-neutral font-bold backdrop-blur-sm bg-neutral/80 text-neutral-content border-none">
                                            {{ $event->category->name }}
                                        </span>
                                    @endif

                                    @if($event->is_featured)
                                        <span class="badge badge-sm badge-accent font-bold text-accent-content border-none shadow-sm">
                                            Tip týdne
                                        </span>
                                    @endif
                                </div>

                                <div class="absolute top-3 right-3">
                                    <span class="badge badge-sm font-bold border-none shadow-sm {{ $event->price_type === 'free' ? 'badge-success text-success-content' : 'badge-primary text-primary-content' }}">
                                        {{ $event->formatted_price }}
                                    </span>
                                </div>

                                {{-- Datum v rohu --}}
                                <div class="absolute bottom-2.5 left-2.5 bg-base-100/90 backdrop-blur-md px-2.5 py-1 rounded-lg text-xs font-bold text-base-content border border-base-300 shadow-sm flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ $event->starts_at->translatedFormat('D d. m. H:i') }}</span>
                                </div>
                            </div>

                            {{-- Tělo karty --}}
                            <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                                <div class="space-y-2">
                                    {{-- Spolek / Pořadatel --}}
                                    <div class="flex items-center gap-2 text-xs">
                                        @if($event->organization)
                                            <span class="badge badge-xs font-bold {{ $event->organization->university === 'MUNI' ? 'badge-info' : ($event->organization->university === 'VUT' ? 'badge-error' : 'badge-ghost') }}">
                                                {{ $event->organization->university }}
                                            </span>
                                            <span class="font-medium text-base-content/80 truncate">
                                                {{ $event->organization->name }}
                                            </span>
                                        @else
                                            <span class="font-medium text-base-content/60">Studentská akce</span>
                                        @endif
                                    </div>

                                    {{-- Titulek --}}
                                    <h3
                                        wire:click="openEventModal({{ $event->id }})"
                                        class="font-black text-lg leading-snug tracking-tight text-base-content group-hover:text-primary transition-colors cursor-pointer line-clamp-2"
                                    >
                                        {{ $event->title }}
                                    </h3>

                                    {{-- Místo konání --}}
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70">
                                        <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="truncate">{{ $event->location_display }}</span>
                                    </div>

                                    {{-- Krátký popis --}}
                                    <p class="text-xs text-base-content/70 line-clamp-2 pt-1">
                                        {{ $event->short_description ?: strip_tags($event->description) }}
                                    </p>
                                </div>

                                {{-- Tlačítka akcí --}}
                                <div class="pt-3 border-t border-base-200 flex items-center justify-between gap-2">
                                    <button
                                        wire:click="openEventModal({{ $event->id }})"
                                        class="btn btn-sm btn-ghost border border-base-300 font-bold text-xs flex-1"
                                    >
                                        Detail akce
                                    </button>

                                    @if($event->ticket_url)
                                        <a
                                            href="{{ $event->ticket_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-sm btn-primary font-bold text-xs gap-1"
                                        >
                                            <span>Lístky</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    @elseif($event->facebook_event_url)
                                        <a
                                            href="{{ $event->facebook_event_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-sm btn-outline border-base-300 font-bold text-xs gap-1"
                                        >
                                            <span>FB Událost</span>
                                        </a>
                                    @else
                                        <button
                                            wire:click="downloadIcal({{ $event->id }})"
                                            class="btn btn-sm btn-ghost border border-base-300 font-bold text-xs"
                                            title="Stáhnout do kalendáře (.ics)"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            + Kalendář
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </main>

    {{-- MODÁLNÍ OKNO PRO DETAIL AKCE --}}
    @if($selectedEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in">
            <div class="card bg-base-100 max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border border-base-300">
                <div class="relative aspect-[16/9] w-full bg-base-300">
                    <img
                        src="{{ $selectedEvent->cover_image ?: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1200&q=80' }}"
                        alt="{{ $selectedEvent->title }}"
                        class="w-full h-full object-cover"
                    />
                    <button
                        wire:click="closeEventModal"
                        class="btn btn-circle btn-sm btn-neutral absolute top-3 right-3 text-neutral-content shadow-md"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="absolute bottom-3 left-3 flex gap-2">
                        <span class="badge badge-primary font-bold shadow-sm">
                            {{ $selectedEvent->formatted_price }}
                        </span>
                        @if($selectedEvent->category)
                            <span class="badge badge-neutral font-bold backdrop-blur-sm shadow-sm">
                                {{ $selectedEvent->category->name }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="card-body p-6 space-y-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs text-base-content/60 mb-1">
                            @if($selectedEvent->organization)
                                <span class="badge badge-xs font-bold badge-outline">{{ $selectedEvent->organization->university }}</span>
                                <span class="font-bold text-primary">{{ $selectedEvent->organization->name }}</span>
                            @endif
                        </div>
                        <h2 class="text-2xl font-black text-base-content">{{ $selectedEvent->title }}</h2>
                    </div>

                    {{-- Klíčové parametry v rámečku --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-4 bg-base-200 rounded-xl border border-base-300 text-xs">
                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <div>
                                <span class="block font-bold text-base-content">Datum a čas:</span>
                                <span class="text-base-content/80">{{ $selectedEvent->starts_at->translatedFormat('l d. F Y od H:i') }}</span>
                                @if($selectedEvent->ends_at)
                                    <span class="block text-base-content/60">do {{ $selectedEvent->ends_at->translatedFormat('d. m. H:i') }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-start gap-2.5">
                            <svg class="w-4 h-4 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <div>
                                <span class="block font-bold text-base-content">Místo konání:</span>
                                <span class="text-base-content/80">{{ $selectedEvent->location_display }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Popis akce --}}
                    <div class="prose max-w-none text-sm text-base-content/90 whitespace-pre-line py-2">
                        {{ $selectedEvent->description ?: $selectedEvent->short_description }}
                    </div>

                    {{-- Tlačítka pro akci --}}
                    <div class="pt-4 border-t border-base-300 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="downloadIcal({{ $selectedEvent->id }})"
                                class="btn btn-sm btn-outline border-base-300 font-bold gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Stáhnout do iCal / Kalendáře
                            </button>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($selectedEvent->facebook_event_url)
                                <a
                                    href="{{ $selectedEvent->facebook_event_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-ghost border border-base-300 font-bold text-xs"
                                >
                                    FB Událost
                                </a>
                            @endif

                            @if($selectedEvent->ticket_url)
                                <a
                                    href="{{ $selectedEvent->ticket_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-sm btn-primary font-bold text-xs gap-1.5"
                                >
                                    <span>Koupit vstupenku</span>
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- FOOTER --}}
    <footer class="mt-16 border-t border-base-300 bg-base-100 py-8 text-xs text-base-content/60 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <span class="font-black text-sm tracking-tight text-base-content">AKCE<span class="text-primary">BRNO</span></span>
                <span>— Otevřený portál studentských akcí a spolků v Brně</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="/admin" class="hover:text-primary transition-colors font-semibold">Administrace pro spolky</a>
                <span>•</span>
                <span>Aktualizováno denně</span>
            </div>
        </div>
    </footer>

@script
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initLeafletMap();
    });

    document.addEventListener('livewire:navigated', function () {
        initLeafletMap();
    });

    window.addEventListener('initMap', function () {
        setTimeout(initLeafletMap, 100);
    });

    let mapInstance = null;

    function initLeafletMap() {
        const mapContainer = document.getElementById('brno-events-map');
        if (!mapContainer || typeof L === 'undefined') return;

        if (mapInstance) {
            mapInstance.remove();
            mapInstance = null;
        }

        const markersData = $wire.mapMarkers || [];

        // Střed Brna
        mapInstance = L.map('brno-events-map').setView([49.1951, 16.6068], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; OpenStreetMap přispěvatelé'
        }).addTo(mapInstance);

        const bounds = [];

        markersData.forEach(function (item) {
            const marker = L.marker([item.lat, item.lng]).addTo(mapInstance);
            bounds.push([item.lat, item.lng]);

            const popupHtml = `
                <div style="font-family: inherit; font-size: 13px; min-width: 180px; padding: 2px;">
                    <strong style="display: block; font-size: 14px; margin-bottom: 3px;">${item.title}</strong>
                    <div style="color: #64748b; font-size: 11px; margin-bottom: 2px;">${item.venue}</div>
                    <div style="color: #ea580c; font-weight: bold; font-size: 12px; margin-bottom: 6px;">${item.time} • ${item.price}</div>
                    <button onclick="$wire.openEventModal(${item.id})" style="background: #ea580c; color: white; border: none; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 11px; cursor: pointer; width: 100%;">Detail akce</button>
                </div>
            `;
            marker.bindPopup(popupHtml);
        });

        if (bounds.length > 0) {
            mapInstance.fitBounds(bounds, { padding: [40, 40] });
        }
    }

    // Přepínač témat: synchronizace s localStorage a atributem data-theme
    window.switchAppTheme = function(themeName) {
        document.documentElement.setAttribute('data-theme', themeName);
        if (themeName === 'events-dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        localStorage.setItem('brno_events_theme', themeName);
        updateThemeButtons(themeName);
    };

    window.updateThemeButtons = function(themeName) {
        const isDark = themeName === 'events-dark';
        const lightBtn = document.getElementById('theme-light-btn');
        const darkBtn = document.getElementById('theme-dark-btn');
        if (lightBtn && darkBtn) {
            if (isDark) {
                darkBtn.classList.add('btn-primary');
                darkBtn.classList.remove('btn-ghost');
                lightBtn.classList.add('btn-ghost');
                lightBtn.classList.remove('btn-primary');
            } else {
                lightBtn.classList.add('btn-primary');
                lightBtn.classList.remove('btn-ghost');
                darkBtn.classList.add('btn-ghost');
                darkBtn.classList.remove('btn-primary');
            }
        }
    };

    // Inicializace tématu při načtení
    (function() {
        const savedTheme = localStorage.getItem('brno_events_theme') || 'events-light';
        window.switchAppTheme(savedTheme);
    })();
</script>
@endscript
</div>
