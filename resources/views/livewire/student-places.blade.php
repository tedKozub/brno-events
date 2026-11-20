<div>
    {{-- KOMPAKTNÍ MOBILNÍ NAVBAR --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 flex items-center gap-2 sm:gap-3">
            {{-- Logo --}}
            <a href="/" wire:navigate class="flex items-center gap-1.5 sm:gap-2.5 group shrink-0">
                {!! \App\Helpers\BrandHelper::logoHtml() !!}
                <span class="hidden xs:inline-block px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider text-primary bg-primary/10 border border-primary/20">
                    {{ \App\Helpers\BrandHelper::badgeText() }}
                </span>
            </a>

            {{-- Středová navigace: Akce vs Místa --}}
            <div class="flex items-center gap-0">
                <div class="hidden xs:block w-px h-5 bg-base-content/20 mr-3 sm:mr-4"></div>
                <nav class="flex items-center gap-0.5 sm:gap-1">
                    <a
                        href="/"
                        wire:navigate
                        class="px-3 sm:px-3.5 py-1.5 rounded-sm font-bold text-xs sm:text-sm inline-flex items-center transition-all text-slate-600 dark:text-slate-400 hover:text-base-content hover:bg-base-200"
                    >
                        <span>Akce</span>
                    </a>
                    <a
                        href="/mista"
                        wire:navigate
                        class="px-3.5 sm:px-4 py-1.5 rounded-sm font-bold text-xs sm:text-sm inline-flex items-center transition-all border border-primary text-primary"
                    >
                        <span class="hidden sm:inline">Studentská místa</span>
                        <span class="sm:hidden">Místa</span>
                    </a>
                </nav>
            </div>

            {{-- Pravá část: 1-tlačítkový rotující přepínač témat (BEZ TEXTU, Systém -> Tmavý -> Světlý) --}}
            <div class="ml-auto"
                x-data="{
                    preference: localStorage.getItem('brno_events_theme') || 'system',
                    cycleTheme() {
                        if (this.preference === 'system') {
                            this.setTheme('dark');
                        } else if (this.preference === 'dark' || this.preference === 'events-dark') {
                            this.setTheme('light');
                        } else {
                            this.setTheme('system');
                        }
                    },
                    setTheme(val) {
                        this.preference = val;
                        localStorage.setItem('brno_events_theme', val);
                        if (window.applyAppTheme) {
                            window.applyAppTheme();
                        }
                        window.dispatchEvent(new CustomEvent('theme-changed', { detail: val }));
                    }
                }"
                @theme-changed.window="preference = $event.detail"
                class="shrink-0"
            >
                <button
                    type="button"
                    @click="cycleTheme()"
                    class="btn btn-sm btn-ghost border border-base-300 bg-base-100 hover:bg-base-200 rounded-xl w-9 h-9 min-h-[36px] p-0 flex items-center justify-center transition-colors shadow-2xs"
                    :title="preference === 'system' ? 'Režim: Systém (kliknutím přepnout na Tmavý)' : ((preference === 'dark' || preference === 'events-dark') ? 'Režim: Tmavý (kliknutím přepnout na Světlý)' : 'Režim: Světlý (kliknutím přepnout na Systém)')"
                    aria-label="Přepnout motiv vzhledu"
                >
                    {{-- 1. Stav: Systém (Auto) - kontrastní půlkruh --}}
                    <svg x-show="preference === 'system'" class="w-4 h-4 text-base-content/80 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <circle cx="12" cy="12" r="9" stroke-width="2" />
                        <path fill="currentColor" d="M12 3a9 9 0 0 1 0 18V3z" />
                    </svg>

                    {{-- 2. Stav: Tmavý - měsíc --}}
                    <svg x-show="preference === 'dark' || preference === 'events-dark'" class="w-4 h-4 text-sky-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>

                    {{-- 3. Stav: Světlý - slunce --}}
                    <svg x-show="preference === 'light' || preference === 'events-light'" class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="4" stroke-width="2"></circle>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32l1.41 1.41M2 12h2m16 0h2M6.34 17.66l-1.41 1.41m14.14-14.14l-1.41 1.41"></path>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    {{-- HLAVNÍ OBSAH --}}
    <main class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6">

        {{-- ÚVODNÍ HLAVIČKA --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-base-content tracking-tight">
                    Studentská místa v Brně
                </h1>
                <p class="text-xs sm:text-sm text-base-content/70 mt-1">
                    Ověřené kavárny na učení, tiché studovny s Eduroamem, místa na rande i studentské hospody.
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="text-xs text-base-content/60 font-semibold hidden sm:block">
                    Celkem <strong class="text-base-content font-black">{{ count($places) }}</strong> míst
                </div>
                <livewire:propose-student-place-modal />
            </div>
        </div>

        {{-- VYHLEDÁVÁNÍ A FILTRY KATEGORIÍ --}}
        <section class="card bg-base-100 shadow-sm border border-base-300 overflow-hidden">
            <div class="card-body p-3 sm:p-5 space-y-3 sm:space-y-3.5">
                {{-- Search bar --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 sm:pl-4 flex items-center pointer-events-none text-base-content/40">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Hledat kavárnu, studovnu, hospodu..."
                        class="input h-11 sm:h-13 input-bordered w-full pl-10 sm:pl-12 pr-10 bg-base-200/60 hover:bg-base-200 text-sm sm:text-base font-semibold focus:border-primary focus:bg-base-100 focus:outline-none rounded-xl sm:rounded-2xl shadow-xs transition-all"
                    />
                    @if(!empty($search))
                        <button
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3 sm:pr-3.5 flex items-center text-base-content/40 hover:text-base-content"
                            title="Vymazat hledání"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>

                {{-- Kategorie Chips --}}
                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none pt-0.5">
                    @foreach($categories as $key => $cat)
                        @php
                            $count = $categoryCounts[$key] ?? 0;
                            $isActive = $selectedCategory === $key;
                        @endphp
                        <button
                            wire:click="setCategory('{{ $key }}')"
                            class="px-4 py-2 rounded-full text-xs font-bold shrink-0 transition-all cursor-pointer whitespace-nowrap border flex items-center gap-1.5 {{ $isActive ? 'bg-primary text-primary-content border-primary shadow-sm' : 'bg-base-200 text-base-content/80 border-base-300 hover:bg-base-300' }}"
                        >
                            <span>{{ $cat['name'] }}</span>
                            <span class="opacity-80 text-[11px] font-semibold">({{ $count }})</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- MAPOVÝ PANEL A SEZNAM MÍST --}}
        <div class="space-y-6">
            {{-- INTERAKTIVNÍ MAPA BRNA --}}
            <section
                id="map-container"
                class="card bg-base-100 shadow-sm border border-base-300 overflow-hidden"
                x-data="studentPlacesController(@js($places))"
                @places-updated.window="updatePlaces($event.detail.places)"
                @place-selected.window="flyToPlace($event.detail.id, $event.detail.lat, $event.detail.lng)"
            >
                <div class="card-body p-3.5 sm:p-5 space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm sm:text-base font-bold text-base-content">
                            Interaktivní mapa míst pro studenty
                        </h2>
                        <span class="text-xs text-base-content/60 font-semibold">
                            Kliknutím na špendlík nebo kartu zobrazíte detail
                        </span>
                    </div>

                    {{-- Mapové plátno s plnou podporou gest z touchpadu i mobilu --}}
                    <div
                        id="student-places-map"
                        x-ref="mapContainer"
                        wire:ignore
                        class="w-full h-[380px] sm:h-[460px] md:h-[500px] rounded-xl z-10 border border-base-300 overflow-hidden relative"
                    ></div>
                </div>
            </section>

            {{-- SEZNAM KARET MÍST --}}
            <section class="space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-black text-base-content tracking-tight">
                        Seznam doporučených míst ({{ count($places) }})
                    </h2>
                </div>

                @if(empty($places))
                    <div class="card bg-base-100 border border-base-300 p-8 text-center space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-base-200 text-base-content/40 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="font-bold text-base text-base-content">Žádné místo neodpovídá zadání</h3>
                        <p class="text-xs text-base-content/70">Zkuste upravit vyhledávací dotaz nebo vybrat jinou kategorii.</p>
                        <button
                            wire:click="$set('search', ''); $set('selectedCategory', 'all');"
                            class="btn btn-sm btn-primary self-center"
                        >
                            Zobrazit všechna místa
                        </button>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($places as $place)
                            @php
                                $categoryName = $categories[$place['category']]['name'] ?? $place['category'];
                                $gMapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($place['name'] . ', ' . $place['address']);
                                $mapyCzUrl = "https://mapy.cz/zakladni?q=" . urlencode($place['name'] . ', ' . $place['address']);

                                $categoryIcon = match($place['category']) {
                                    'study' => '<svg class="w-3.5 h-3.5 text-purple-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>',
                                    'cafes' => '<svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 8h1a3 3 0 010 6h-1M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8zM6 3v2M10 3v2M14 3v2"/></svg>',
                                    'date' => '<svg class="w-3.5 h-3.5 text-pink-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
                                    'pubs' => '<svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11h1a3 3 0 010 6h-1M4 9h15v10a2 2 0 01-2 2H6a2 2 0 01-2-2V9zM7 5v4M11 5v4M15 5v4"/></svg>',
                                    'parks' => '<svg class="w-3.5 h-3.5 text-cyan-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2L4 12h3v8h10v-8h3L12 2z"/></svg>',
                                    default => '<svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                                };
                            @endphp
                            <div
                                class="card bg-base-100 border border-base-300 hover:border-primary/50 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group"
                            >
                                <div class="card-body p-4 sm:p-5 space-y-2.5">
                                    {{-- Název & adresa s minimalistickou ikonou kategorie --}}
                                    <div>
                                        <h3
                                            @click="flyToPlace({{ $place['id'] }}, {{ $place['lat'] }}, {{ $place['lng'] }})"
                                            class="font-black text-base sm:text-lg text-base-content group-hover:text-primary transition-colors cursor-pointer"
                                        >
                                            {{ $place['name'] }}
                                        </h3>
                                        <div class="flex items-center gap-1.5 mt-1 text-xs text-base-content/65 font-medium min-w-0">
                                            <span class="inline-flex items-center shrink-0" title="{{ $categoryName }}">
                                                {!! $categoryIcon !!}
                                            </span>
                                            <span class="truncate">{{ $place['address'] }}</span>
                                        </div>
                                    </div>

                                    {{-- Popis --}}
                                    <p class="text-xs text-base-content/80 leading-relaxed">
                                        {{ $place['description'] }}
                                    </p>
                                </div>

                                {{-- Spodní akční tlačítka: Ukázat na mapě + Navigace (jednoduchá plná tlačítka, bez skleněného designu a bez ikon) --}}
                                <div class="px-4 pb-4 pt-1 sm:px-5 sm:pb-5 border-t border-base-200 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="$dispatch('place-selected', { id: {{ $place['id'] }}, lat: {{ $place['lat'] }}, lng: {{ $place['lng'] }} })"
                                        class="btn btn-sm btn-ghost border border-base-300 hover:bg-base-200 text-base-content flex-1 font-bold text-xs h-9 min-h-[36px] px-3.5 rounded-xl transition-colors"
                                        title="Vycentrovat na mapě"
                                    >
                                        <span>Na mapě</span>
                                    </button>

                                    <a
                                        href="{{ $gMapsUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm bg-[#0284c7] hover:bg-[#0369a1] text-white border-none font-bold text-xs px-3.5 sm:px-4 h-9 min-h-[36px] rounded-xl transition-colors shadow-xs"
                                        title="Otevřít v Google Maps"
                                    >
                                        <span>Google</span>
                                    </a>

                                    <a
                                        href="{{ $mapyCzUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm bg-[#16a34a] hover:bg-[#15803d] text-white border-none font-bold text-xs px-3.5 sm:px-4 h-9 min-h-[36px] rounded-xl transition-colors shadow-xs"
                                        title="Otevřít v Mapy.cz"
                                    >
                                        <span>Mapy.cz</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>
    </main>

    {{-- FOOTER SE VSTUPEM PRO SPOLKY A PWA INSTALACÍ --}}
    <x-footer />
</div>

@script
<script>
    window.studentPlacesController = function(initialPlaces) {
        return {
            places: initialPlaces || [],
            map: null,
            markersLayer: null,
            markers: {},
            _resizeHandler: null,

            init() {
                this.$nextTick(() => {
                    if (typeof L !== 'undefined') {
                        this.initMap();
                    } else {
                        window.addEventListener('load', () => this.initMap(), { once: true });
                    }
                });

                this._resizeHandler = () => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                };
                window.addEventListener('resize', this._resizeHandler, { passive: true });
            },

            initMap() {
                const mapEl = this.$refs.mapContainer || document.getElementById('student-places-map');
                if (!mapEl || typeof L === 'undefined') return;

                if (this.map) {
                    try {
                        this.map.remove();
                    } catch (e) {}
                    this.map = null;
                }

                if (mapEl._leaflet_id) {
                    delete mapEl._leaflet_id;
                }

                // Shodná konfigurace s mapou akcí s plnou podporou gest z touchpadu i mobilu:
                this.map = L.map(mapEl, {
                    scrollWheelZoom: true,   // Povolí touchpad scroll & pinch zoom i kolečko myši
                    touchZoom: true,         // Povolí multi-touch pinch zoom na telefonech
                    dragging: true,          // Povolí posun prstem i myší
                    tap: true,               // Povolí dotyková gesta na mobilních zařízeních
                    doubleClickZoom: true,   // Dvojitý tap pro přiblížení
                    boxZoom: true,
                    keyboard: true,
                    zoomAnimation: true,
                    fadeAnimation: true,
                    markerZoomAnimation: true,
                }).setView([49.1951, 16.6068], 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(this.map);

                this.markersLayer = L.layerGroup().addTo(this.map);

                this.renderMarkers();

                setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                }, 200);
            },

            updatePlaces(newPlaces) {
                this.places = newPlaces || [];
                if (!this.map) {
                    this.initMap();
                    return;
                }
                this.renderMarkers();
                setTimeout(() => {
                    if (this.map) {
                        this.map.invalidateSize();
                    }
                }, 100);
            },

            renderMarkers() {
                if (!this.map || typeof L === 'undefined') return;

                if (!this.markersLayer) {
                    this.markersLayer = L.layerGroup().addTo(this.map);
                } else {
                    this.markersLayer.clearLayers();
                }
                this.markers = {};

                const bounds = [];
                const categoryColors = {
                    study: '#8b5cf6',
                    cafes: '#f59e0b',
                    date: '#ec4899',
                    pubs: '#10b981',
                    parks: '#06b6d4',
                };
                const categoryIcons = {
                    study: `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v16"/><path d="M20.001 19A2 2 0 0 0 22 17V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h4a5 5 0 0 1 4 2 5 5 0 0 1 4-2z"/></svg>`,
                    cafes: `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 2v2"/><path d="M14 2v2"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 1 1 0 8h-1"/><path d="M6 2v2"/></svg>`,
                    date: `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>`,
                    pubs: `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 11h1a3 3 0 0 1 0 6h-1"/><path d="M9 12v6"/><path d="M13 12v6"/><path d="M14 7.5c-1 0-1.44.5-3 .5s-2-.5-3-.5-1.72.5-2.5.5a2.5 2.5 0 0 1 0-5c.78 0 1.57.5 2.5.5S9.44 2 11 2s2 1.5 3 1.5 1.72-.5 2.5-.5a2.5 2.5 0 0 1 0 5c-.78 0-1.5-.5-2.5-.5Z"/><path d="M5 8v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V8"/></svg>`,
                    parks: `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 10v.2A3 3 0 0 1 8.9 16H5a3 3 0 0 1-1-5.8V10a3 3 0 0 1 6 0Z"/><path d="M7 16v6"/><path d="M13 19v3"/><path d="M12 19h8.3a1 1 0 0 0 .7-1.7L18 14h.3a1 1 0 0 0 .7-1.7L16 9h.2a1 1 0 0 0 .8-1.7L13 3l-1.4 1.5"/></svg>`,
                    default: `<svg style="width: 16px; height: 16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/></svg>`,
                };

                // 1. Seskupení míst podle shodných souřadnic pro eliminaci překryvů
                const groups = {};
                (this.places || []).forEach(place => {
                    if (!place.lat || !place.lng) return;
                    const key = `${place.lat.toFixed(4)}_${place.lng.toFixed(4)}`;
                    if (!groups[key]) {
                        groups[key] = {
                            centerLat: place.lat,
                            centerLng: place.lng,
                            items: []
                        };
                    }
                    groups[key].items.push(place);
                });

                const escapeHtml = (text) => {
                    if (text === null || text === undefined) return '';
                    return String(text)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };

                const createPopupContent = (place, color, iconSvg) => {
                    const safeLat = encodeURIComponent(place.lat);
                    const safeLng = encodeURIComponent(place.lng);
                    const gMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + safeLat + ',' + safeLng;
                    const mapyCzUrl = 'https://mapy.cz/zakladni?q=' + safeLat + ',' + safeLng;
                    const safeName = escapeHtml(place.name);
                    const safeAddress = escapeHtml(place.address);
                    const safeDesc = escapeHtml(place.description);

                    return `
                        <div style="font-family: inherit; font-size: 13px; min-width: 220px; max-width: 290px; padding: 4px;">
                            <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                                <span style="display: inline-flex; align-items: center; justify-content: center; width: 20px; height: 20px; border-radius: 6px; background-color: ${color}; color: #ffffff; shrink: 0;">
                                    ${iconSvg.replace('width: 16px; height: 16px;', 'width: 12px; height: 12px;')}
                                </span>
                            </div>
                            <h3 style="font-weight: 900; font-size: 14px; line-height: 1.25; margin-bottom: 4px; color: #0f172a;">
                                ${safeName}
                            </h3>
                            <p style="font-size: 11px; color: #64748b; margin-bottom: 6px;">
                                ${safeAddress}
                            </p>
                            <p style="font-size: 12px; color: #334155; line-height: 1.4; margin-bottom: 10px;">
                                ${safeDesc}
                            </p>
                            <div style="display: flex; gap: 6px;">
                                <a href="${gMapsUrl}" target="_blank" rel="noopener noreferrer" style="flex: 1; padding: 6px 8px; font-size: 11px; font-weight: 700; background-color: #0284c7; color: white; border-radius: 8px; text-decoration: none; text-align: center;">
                                    Google Maps
                                </a>
                                <a href="${mapyCzUrl}" target="_blank" rel="noopener noreferrer" style="flex: 1; padding: 6px 8px; font-size: 11px; font-weight: 700; background-color: #16a34a; color: white; border-radius: 8px; text-decoration: none; text-align: center;">
                                    Mapy.cz
                                </a>
                            </div>
                        </div>
                    `;
                };

                const calculateOffsetPos = (centerLat, centerLng, index, total, radiusMeters) => {
                    if (total <= 1 || radiusMeters === 0) {
                        return [centerLat, centerLng];
                    }
                    const angle = (2 * Math.PI * index) / total - (Math.PI / 2);
                    const latOffset = (radiusMeters / 111111) * Math.sin(angle);
                    const lngOffset = (radiusMeters / (111111 * Math.cos(centerLat * Math.PI / 180))) * Math.cos(angle);
                    return [centerLat + latOffset, centerLng + lngOffset];
                };

                Object.values(groups).forEach(group => {
                    const total = group.items.length;
                    const center = [group.centerLat, group.centerLng];
                    bounds.push(center);

                    if (total === 1) {
                        const place = group.items[0];
                        const color = categoryColors[place.category] || '#6366f1';
                        const iconSvg = categoryIcons[place.category] || categoryIcons.default;
                        const customIcon = L.divIcon({
                            className: 'custom-student-pin',
                            html: `
                                <div style="position: relative; display: flex; flex-direction: column; align-items: center; cursor: pointer; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.32));">
                                    <div style="background-color: ${color}; width: 34px; height: 34px; border-radius: 50%; border: 2.5px solid #ffffff; display: flex; align-items: center; justify-content: center; color: #ffffff;">
                                        ${iconSvg}
                                    </div>
                                    <div style="width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-top: 7px solid ${color}; margin-top: -2px;"></div>
                                </div>
                            `,
                            iconSize: [34, 42],
                            iconAnchor: [17, 40],
                            popupAnchor: [0, -42],
                        });

                        const popupContent = createPopupContent(place, color, iconSvg);

                        const marker = L.marker(center, { icon: customIcon })
                            .addTo(this.markersLayer)
                            .bindPopup(popupContent);

                        this.markers[place.id] = marker;
                        return;
                    }

                    // Vícenásobná místa se stejnou adresou / souřadnicemi
                    const baseRadius = total === 2 ? 18 : (total <= 4 ? 24 : 30);
                    const hoverRadius = baseRadius * 1.85;

                    const markersInGroup = [];
                    const linesInGroup = [];

                    const centerDot = L.circleMarker(center, {
                        radius: 5,
                        color: '#6366f1',
                        fillColor: '#ffffff',
                        fillOpacity: 1,
                        weight: 2.5
                    }).addTo(this.markersLayer);

                    group.items.forEach((place, idx) => {
                        const color = categoryColors[place.category] || '#6366f1';
                        const iconSvg = categoryIcons[place.category] || categoryIcons.default;
                        const basePos = calculateOffsetPos(group.centerLat, group.centerLng, idx, total, baseRadius);
                        const hoverPos = calculateOffsetPos(group.centerLat, group.centerLng, idx, total, hoverRadius);

                        const leg = L.polyline([center, basePos], {
                            color: color,
                            weight: 1.5,
                            opacity: 0.55,
                            dashArray: '3, 4'
                        }).addTo(this.markersLayer);
                        linesInGroup.push({ leg, idx });

                        const customIcon = L.divIcon({
                            className: 'custom-student-pin',
                            html: `
                                <div style="position: relative; display: flex; flex-direction: column; align-items: center; cursor: pointer; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.32)); transition: transform 0.18s ease;">
                                    <div style="background-color: ${color}; width: 34px; height: 34px; border-radius: 50%; border: 2.5px solid #ffffff; display: flex; align-items: center; justify-content: center; color: #ffffff;">
                                        ${iconSvg}
                                    </div>
                                    <div style="position: absolute; top: -5px; right: -5px; background: #0f172a; color: #ffffff; border: 1.5px solid #ffffff; width: 18px; height: 18px; border-radius: 50%; font-size: 10px; font-weight: 900; display: flex; align-items: center; justify-content: center; z-index: 10;">
                                        ${idx + 1}
                                    </div>
                                    <div style="width: 0; height: 0; border-left: 6px solid transparent; border-right: 6px solid transparent; border-top: 7px solid ${color}; margin-top: -2px;"></div>
                                </div>
                            `,
                            iconSize: [34, 42],
                            iconAnchor: [17, 40],
                            popupAnchor: [0, -42],
                        });

                        const popupContent = createPopupContent(place, color, iconSvg);

                        const marker = L.marker(basePos, { icon: customIcon, zIndexOffset: 100 + idx })
                            .addTo(this.markersLayer)
                            .bindPopup(popupContent);

                        this.markers[place.id] = marker;
                        markersInGroup.push({ marker, basePos, hoverPos, idx });
                    });

                    let isExpanded = false;
                    const expandGroup = () => {
                        if (isExpanded) return;
                        isExpanded = true;
                        markersInGroup.forEach(m => {
                            m.marker.setLatLng(m.hoverPos);
                            m.marker.setZIndexOffset(600 + m.idx);
                        });
                        linesInGroup.forEach(l => {
                            l.leg.setLatLngs([center, markersInGroup[l.idx].hoverPos]);
                            l.leg.setStyle({ opacity: 0.95, weight: 2 });
                        });
                    };

                    const collapseGroup = () => {
                        if (!isExpanded) return;
                        isExpanded = false;
                        markersInGroup.forEach(m => {
                            m.marker.setLatLng(m.basePos);
                            m.marker.setZIndexOffset(100 + m.idx);
                        });
                        linesInGroup.forEach(l => {
                            l.leg.setLatLngs([center, markersInGroup[l.idx].basePos]);
                            l.leg.setStyle({ opacity: 0.55, weight: 1.5 });
                        });
                    };

                    markersInGroup.forEach(m => {
                        m.marker.on('mouseover', expandGroup);
                        m.marker.on('mouseout', collapseGroup);
                    });
                    centerDot.on('mouseover', expandGroup);
                    centerDot.on('mouseout', collapseGroup);
                });

                if (bounds.length > 0 && this.map) {
                    this.map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
                }
            },

            flyToPlace(id, lat, lng) {
                if (!this.map) return;
                this.map.flyTo([lat, lng], 16, { duration: 1.2 });
                const marker = this.markers[id];
                if (marker) {
                    setTimeout(() => marker.openPopup(), 600);
                }
                const mapCard = document.getElementById('map-container');
                if (mapCard) {
                    mapCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },

            destroy() {
                if (this._resizeHandler) {
                    window.removeEventListener('resize', this._resizeHandler);
                }
                if (this.map) {
                    try {
                        this.map.remove();
                    } catch (e) {}
                    this.map = null;
                }
            }
        };
    };
</script>
@endscript
