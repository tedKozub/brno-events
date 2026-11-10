<div>
    {{-- KOMPAKTNÍ MOBILNÍ NAVBAR --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-3">
            {{-- Logo --}}
            <a href="/" class="flex items-center gap-2 sm:gap-3 group shrink-0">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary flex items-center justify-center text-primary-content font-black shadow-md shadow-primary/20 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="font-black text-base sm:text-xl tracking-tight text-base-content">
                        AKCE<span class="text-primary">BRNO</span>
                    </span>
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider text-primary bg-primary/10 border border-primary/20">
                        Student
                    </span>
                </div>
            </a>

            {{-- Pravá část: Přepínač témat (Alpine) s vysokým kontrastem ikon --}}
            <div
                x-data="{
                    theme: localStorage.getItem('brno_events_theme') || 'events-light',
                    setTheme(val) {
                        this.theme = val;
                        document.documentElement.setAttribute('data-theme', val);
                        if (val === 'events-dark') {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                        localStorage.setItem('brno_events_theme', val);
                    }
                }"
                class="inline-flex items-center bg-base-200 p-0.5 sm:p-1 rounded-xl border border-base-300"
            >
                <button
                    type="button"
                    @click="setTheme('events-light')"
                    :class="theme === 'events-light' ? 'bg-primary text-white shadow-sm' : 'text-base-content/70 hover:text-base-content'"
                    class="px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all"
                    title="Světlý režim"
                >
                    <svg
                        :class="theme === 'events-light' ? 'text-white' : 'text-amber-500'"
                        class="w-4 h-4 shrink-0 transition-colors"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span class="hidden sm:inline font-bold">Světlý</span>
                </button>
                <button
                    type="button"
                    @click="setTheme('events-dark')"
                    :class="theme === 'events-dark' ? 'bg-primary text-white shadow-sm' : 'text-base-content/70 hover:text-base-content'"
                    class="px-2.5 sm:px-3 py-1 rounded-lg text-xs font-bold flex items-center gap-1.5 transition-all"
                    title="Tmavý režim"
                >
                    <svg
                        :class="theme === 'events-dark' ? 'text-white' : 'text-sky-400'"
                        class="w-4 h-4 shrink-0 transition-colors"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                    <span class="hidden sm:inline font-bold">Tmavý</span>
                </button>
            </div>
        </div>
    </header>

    {{-- HLAVNÍ OBSAHOVÁ OBLAST --}}
    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-5">

        {{-- FILTROVACÍ PANEL --}}
        <section class="card bg-base-100 shadow-sm border border-base-300 overflow-hidden">
            <div class="card-body p-3.5 sm:p-6 space-y-4">
                {{-- 1. Vyhledávání a přepínač zobrazení --}}
                <div class="flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                    {{-- Search Input --}}
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-base-content/40">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Hledat akci, hospodský kvíz, párty, turnaj..."
                            class="input input-sm sm:input-md input-bordered w-full pl-10 sm:pl-11 bg-base-200/50 text-xs sm:text-sm font-medium focus:border-primary focus:outline-none rounded-xl"
                        />
                        @if(!empty($search))
                            <button
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-base-content/40 hover:text-base-content"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- Přepínač pohledů: Karty vs Mapa --}}
                    <div class="join bg-base-200 p-0.5 sm:p-1 rounded-xl border border-base-300 w-full sm:w-auto shrink-0">
                        <button
                            wire:click="setViewMode('cards')"
                            class="btn btn-xs sm:btn-sm join-item flex-1 sm:flex-initial gap-1.5 font-bold text-xs {{ $viewMode === 'cards' ? 'btn-primary' : 'btn-ghost text-base-content/70' }}"
                        >
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                            </svg>
                            <span>Přehled</span>
                        </button>
                        <button
                            wire:click="setViewMode('map')"
                            class="btn btn-xs sm:btn-sm join-item flex-1 sm:flex-initial gap-1.5 font-bold text-xs {{ $viewMode === 'map' ? 'btn-primary' : 'btn-ghost text-base-content/70' }}"
                        >
                            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                            </svg>
                            <span>Mapa Brna</span>
                        </button>
                    </div>
                </div>

                {{-- 2. Časové filtry (Bez víkendu – studenti přes víkend akce nemají!) --}}
                <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 scrollbar-none">
                    <span class="text-[11px] font-black uppercase tracking-wider text-base-content/50 pr-1 shrink-0">Kdy:</span>
                    @php
                        $dateOptions = [
                            'all' => 'Všechny dny',
                            'today' => 'Dnes',
                            'tomorrow' => 'Zítra',
                            'this_week' => 'Tento týden',
                            'next_week' => 'Příští týden',
                        ];
                    @endphp
                    @foreach($dateOptions as $key => $label)
                        <button
                            wire:click="setDateFilter('{{ $key }}')"
                            class="px-3.5 py-1.5 sm:py-2 rounded-xl text-xs font-bold shrink-0 transition-all cursor-pointer whitespace-nowrap {{ $dateFilter === $key ? 'bg-primary text-primary-content shadow-sm' : 'bg-base-200 text-base-content/80 hover:bg-base-300 border border-base-300' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- 3. Univerzity a Vstupné --}}
                <div class="flex flex-wrap items-center justify-between gap-2.5 pt-2 border-t border-base-200">
                    {{-- Univerzity --}}
                    <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                        <span class="text-[11px] font-black uppercase tracking-wider text-base-content/50 pr-1 shrink-0">Škola:</span>
                        @foreach(['' => 'Všechny', 'MUNI' => 'MUNI', 'VUT' => 'VUT', 'MENDELU' => 'MENDELU', 'UNOB' => 'UNOB', 'VETUNI' => 'VETUNI'] as $code => $uLabel)
                            <button
                                wire:click="setUniversity('{{ $code }}')"
                                class="px-3 py-1 rounded-lg text-xs font-bold shrink-0 transition-all cursor-pointer whitespace-nowrap {{ $selectedUniversity === $code ? 'bg-neutral text-neutral-content shadow-xs' : 'bg-base-200/80 text-base-content/70 hover:bg-base-300' }}"
                            >
                                {{ $uLabel }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Vstupné zdarma toggle --}}
                    <div class="flex items-center gap-2">
                        <label class="cursor-pointer label gap-2 py-0 px-1">
                            <span class="label-text text-xs font-bold text-base-content/80">Pouze zdarma</span>
                            <input
                                type="checkbox"
                                wire:click="toggleFree"
                                {{ $onlyFree ? 'checked' : '' }}
                                class="toggle toggle-primary toggle-sm"
                            />
                        </label>
                    </div>
                </div>

                {{-- 4. Kategorie chips s velkorysým paddingem (žádné přetékání!) --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none pt-1">
                    <button
                        wire:click="setCategory('')"
                        class="px-4 py-1.5 rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer whitespace-nowrap border {{ empty($selectedCategory) ? 'bg-primary text-primary-content border-primary shadow-sm' : 'bg-base-200 text-base-content/80 border-base-300 hover:bg-base-300' }}"
                    >
                        Všechny kategorie
                    </button>
                    @foreach($categories as $cat)
                        <button
                            wire:click="setCategory('{{ $cat->slug }}')"
                            class="px-4 py-1.5 rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer whitespace-nowrap border flex items-center gap-1.5 {{ $selectedCategory === $cat->slug ? 'bg-primary text-primary-content border-primary shadow-sm' : 'bg-base-200 text-base-content/80 border-base-300 hover:bg-base-300' }}"
                        >
                            <span>{{ $cat->name }}</span>
                            @if($cat->events_count > 0)
                                <span class="opacity-75 text-[11px] font-semibold">({{ $cat->events_count }})</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- STATUSOVÁ LIŠTA S POČTEM VÝSLEDKŮ --}}
        <div class="flex items-center justify-between px-1 text-xs text-base-content/70">
            <div>
                Zobrazeno <strong class="text-base-content font-black">{{ $events->count() }}</strong> z {{ $totalUpcomingCount }} studentských akcí
                @if(!empty($search) || !empty($selectedCategory) || !empty($selectedUniversity) || $dateFilter !== 'all' || $onlyFree)
                    <span class="text-primary font-bold">(filtrováno)</span>
                @endif
            </div>

            @if(!empty($search) || !empty($selectedCategory) || !empty($selectedUniversity) || $dateFilter !== 'all' || $onlyFree)
                <button
                    wire:click="resetFilters"
                    class="text-error hover:underline font-bold text-xs flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Zrušit filtry
                </button>
            @endif
        </div>

        {{-- ZOBRAZENÍ: MAPA BRNA --}}
        @if($viewMode === 'map')
            <section class="card bg-base-100 shadow-sm border border-base-300 overflow-hidden">
                <div class="card-body p-3.5 sm:p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm sm:text-base font-bold flex items-center gap-2 text-base-content">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Místa akcí a kluby v Brně
                        </h2>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-base-200 text-base-content/80 border border-base-300">
                            {{ count($mapMarkers) }} míst s akcí
                        </span>
                    </div>

                    <div
                        id="brno-events-map"
                        data-markers='@json($mapMarkers)'
                        class="w-full h-[420px] sm:h-[500px] rounded-xl bg-base-200 border border-base-300 z-10"
                    ></div>
                </div>
            </section>
        @endif

        {{-- ZOBRAZENÍ: SEZNAM KARET --}}
        @if($viewMode === 'cards')
            @if($events->isEmpty())
                <div class="card bg-base-100 border border-base-300 p-8 sm:p-12 text-center">
                    <div class="max-w-md mx-auto space-y-3">
                        <div class="w-12 h-12 rounded-full bg-base-200 mx-auto flex items-center justify-center text-base-content/40">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-base sm:text-lg font-bold">Žádná akce neodpovídá zvoleným filtrům</h3>
                        <p class="text-xs text-base-content/70">Zkuste rozšířit časové rozmezí nebo vyčistit hledaný výraz.</p>
                        <button wire:click="resetFilters" class="btn btn-primary btn-sm font-bold">
                            Zobrazit všechny nadcházející akce
                        </button>
                    </div>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
                    @foreach($events as $event)
                        <div class="card bg-base-100 shadow-xs hover:shadow-lg border border-base-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group rounded-2xl">
                            {{-- Horní část: Obrázek & Odznaky s velkorysým paddingem --}}
                            <a href="/akce/{{ $event->slug }}" wire:navigate class="relative aspect-[16/9] w-full bg-base-300 overflow-hidden block">
                                <img
                                    src="{{ $event->cover_image ?: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=800&q=80' }}"
                                    alt="{{ $event->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />

                                {{-- Odznaky přes obrázek --}}
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                    @if($event->category)
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold backdrop-blur-md bg-neutral/85 text-neutral-content shadow-sm whitespace-nowrap">
                                            {{ $event->category->name }}
                                        </span>
                                    @endif

                                    @if($event->is_featured)
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-accent text-accent-content shadow-sm whitespace-nowrap">
                                            Tip
                                        </span>
                                    @endif
                                </div>

                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold shadow-sm whitespace-nowrap {{ $event->price_type === 'free' ? 'bg-success text-success-content' : 'bg-primary text-primary-content' }}">
                                        {{ $event->formatted_price }}
                                    </span>
                                </div>

                                {{-- Datum v levém dolním rohu --}}
                                <div class="absolute bottom-2.5 left-2.5 bg-base-100/95 backdrop-blur-md px-3 py-1 rounded-lg text-xs font-bold text-base-content border border-base-300 shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ $event->starts_at->translatedFormat('D d. m. H:i') }}</span>
                                </div>
                            </a>

                            {{-- Tělo karty --}}
                            <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
                                <div class="space-y-2">
                                    {{-- Spolek / Pořadatel s jasně čitelným odznakem --}}
                                    <div class="flex items-center gap-2 text-xs">
                                        @if($event->organization)
                                            @php
                                                $uBadge = match($event->organization->university) {
                                                    'MUNI' => 'bg-info/15 text-info border border-info/20',
                                                    'VUT' => 'bg-error/15 text-error border border-error/20',
                                                    'MENDELU' => 'bg-success/15 text-success border border-success/20',
                                                    'UNOB' => 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30',
                                                    'VETUNI' => 'bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
                                                    default => 'bg-neutral/15 text-base-content/70 border border-base-300',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 rounded text-[11px] font-black uppercase tracking-wider {{ $uBadge }}">
                                                {{ $event->organization->university }}
                                            </span>
                                            <span class="font-semibold text-base-content/80 truncate">
                                                {{ $event->organization->name }}
                                            </span>
                                        @else
                                            <span class="font-semibold text-base-content/60">Studentská akce</span>
                                        @endif
                                    </div>

                                    {{-- Titulek s odkazem --}}
                                    <a
                                        href="/akce/{{ $event->slug }}"
                                        wire:navigate
                                        class="font-black text-base sm:text-lg leading-snug tracking-tight text-base-content group-hover:text-primary transition-colors cursor-pointer line-clamp-2 block"
                                    >
                                        {{ $event->title }}
                                    </a>

                                    {{-- Místo konání --}}
                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70">
                                        <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="truncate">{{ $event->location_display }}</span>
                                    </div>

                                    {{-- Krátký popis --}}
                                    <p class="text-xs text-base-content/70 line-clamp-2 pt-1 leading-relaxed">
                                        {{ $event->short_description ?: strip_tags($event->description) }}
                                    </p>
                                </div>

                                {{-- Tlačítka akcí --}}
                                <div class="pt-3 border-t border-base-200 flex items-center justify-between gap-2">
                                    <a
                                        href="/akce/{{ $event->slug }}"
                                        wire:navigate
                                        class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 hover:btn-primary font-bold text-xs flex-1 rounded-xl"
                                    >
                                        <span>Detail akce</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>

                                    @if($event->ticket_url)
                                        <a
                                            href="{{ $event->ticket_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-xs sm:btn-sm btn-primary font-bold text-xs gap-1 rounded-xl"
                                        >
                                            <span>Lístky</span>
                                            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    @elseif($event->facebook_event_url)
                                        <a
                                            href="{{ $event->facebook_event_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="btn btn-xs sm:btn-sm btn-outline border-base-300 font-bold text-xs gap-1 rounded-xl"
                                        >
                                            <span>FB Událost</span>
                                        </a>
                                    @else
                                        <button
                                            wire:click="downloadIcal({{ $event->id }})"
                                            class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 font-bold text-xs rounded-xl"
                                            title="Uložit do kalendáře (.ics)"
                                        >
                                            <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

    {{-- FOOTER SE VSTUPEM PRO SPOLKY --}}
    <footer class="mt-14 border-t border-base-300 bg-base-100 py-8 text-xs text-base-content/70 transition-colors">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
            <div class="space-y-1">
                <div class="flex items-center justify-center sm:justify-start gap-2">
                    <span class="font-black text-sm tracking-tight text-base-content">AKCE<span class="text-primary">BRNO</span></span>
                    <span>— Studentské akce a spolky v Brně</span>
                </div>
                <p class="text-[11px] text-base-content/50">Otevřená nástěnka pro studentský život na MUNI, VUT, MENDELU, UNOB & VETUNI.</p>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-3">
                <a
                    href="/admin"
                    class="btn btn-sm btn-outline border-base-300 font-bold text-xs gap-2 rounded-xl hover:bg-base-200 text-base-content hover:text-primary transition-all px-4"
                >
                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Vstup a přidání akce pro spolky</span>
                </a>
            </div>
        </div>
    </footer>
</div>

@script
<script>
    let leafletMapInstance = null;

    function renderLeafletMap() {
        const mapContainer = document.getElementById('brno-events-map');
        if (!mapContainer || typeof L === 'undefined') return;

        if (leafletMapInstance) {
            leafletMapInstance.remove();
            leafletMapInstance = null;
        }

        let markers = [];
        try {
            markers = JSON.parse(mapContainer.getAttribute('data-markers') || '[]');
        } catch (e) {
            markers = [];
        }

        // Vytvoření mapy vycentrované na Brno
        leafletMapInstance = L.map('brno-events-map', {
            scrollWheelZoom: false
        }).setView([49.1951, 16.6068], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(leafletMapInstance);

        // Vlastní ostrý oranžový SVG pin (žádné chybějící PNG ikony!)
        const pinIcon = L.divIcon({
            className: 'custom-map-pin',
            html: `
                <div style="background-color: #ea580c; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2.5px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.35); cursor: pointer;">
                    <svg style="width: 17px; height: 17px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -34]
        });

        const bounds = [];

        markers.forEach(function (item) {
            if (item.lat && item.lng) {
                const marker = L.marker([item.lat, item.lng], { icon: pinIcon }).addTo(leafletMapInstance);
                bounds.push([item.lat, item.lng]);

                const popupHtml = `
                    <div style="font-family: inherit; font-size: 13px; min-width: 200px; padding: 4px;">
                        <div style="font-size: 10px; font-weight: 800; color: #ea580c; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 2px;">
                            ${item.category || 'Akce'}
                        </div>
                        <strong style="display: block; font-size: 14px; font-weight: 900; line-height: 1.25; margin-bottom: 4px; color: #0f172a;">
                            ${item.title}
                        </strong>
                        <div style="color: #64748b; font-size: 11px; margin-bottom: 4px;">
                            ${item.venue}
                        </div>
                        <div style="font-weight: 800; font-size: 12px; color: #ea580c; margin-bottom: 8px;">
                            ${item.time} • ${item.price}
                        </div>
                        <a href="/akce/${item.slug || item.id}" style="display: block; text-align: center; text-decoration: none; background: #ea580c; color: #ffffff; padding: 7px 12px; border-radius: 8px; font-weight: 800; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer;">
                            Zobrazit detail akce &rarr;
                        </a>
                    </div>
                `;
                marker.bindPopup(popupHtml);
            }
        });

        if (bounds.length > 0) {
            leafletMapInstance.fitBounds(bounds, { padding: [40, 40] });
        }

        setTimeout(function () {
            if (leafletMapInstance) {
                leafletMapInstance.invalidateSize();
            }
        }, 150);
    }

    // Inicializace a posluchače
    document.addEventListener('DOMContentLoaded', renderLeafletMap);
    document.addEventListener('livewire:navigated', renderLeafletMap);
    window.addEventListener('initMap', function () {
        setTimeout(renderLeafletMap, 50);
    });
</script>
@endscript
