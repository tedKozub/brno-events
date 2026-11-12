<div>
    {{-- KOMPAKTNÍ MOBILNÍ NAVBAR --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-2 sm:gap-3">
            {{-- Logo --}}
            <a href="/" wire:navigate class="flex items-center gap-2 sm:gap-3 group shrink-0">
                <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl bg-primary flex items-center justify-center text-primary-content font-black shadow-md shadow-primary/20 group-hover:scale-105 transition-transform">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2">
                    <span class="font-black text-base sm:text-xl tracking-tight text-base-content">
                        AKCE<span class="text-primary">BRNO</span>
                    </span>
                    <span class="hidden xs:inline-block px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider text-primary bg-primary/10 border border-primary/20">
                        Student
                    </span>
                </div>
            </a>

            {{-- Středová navigace: Akce vs Místa --}}
            <nav class="flex items-center gap-1 sm:gap-2">
                <a
                    href="/"
                    wire:navigate
                    class="px-3 sm:px-3.5 py-1.5 rounded-xl font-bold text-xs sm:text-sm inline-flex items-center gap-1.5 transition-all text-base-content/70 hover:text-base-content hover:bg-base-200"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Akce</span>
                </a>
                <a
                    href="/mista"
                    wire:navigate
                    class="px-3 sm:px-3.5 py-1.5 rounded-xl font-bold text-xs sm:text-sm inline-flex items-center gap-1.5 transition-all bg-primary text-primary-content shadow-xs"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="hidden sm:inline">Studentská místa</span>
                    <span class="sm:hidden">Místa</span>
                </a>
            </nav>

            {{-- Pravá část: 1-tlačítkový rotující přepínač témat (BEZ TEXTU, Systém -> Tmavý -> Světlý) --}}
            <div
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
                <h1 class="text-2xl sm:text-3xl font-black text-base-content tracking-tight flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </span>
                    Studentská místa v Brně
                </h1>
                <p class="text-xs sm:text-sm text-base-content/70 mt-1">
                    Ověřené kavárny na učení, tiché studovny s Eduroamem, místa na rande i studentské hospody.
                </p>
            </div>
            <div class="text-xs text-base-content/60 font-semibold shrink-0">
                Celkem <strong class="text-base-content font-black">{{ count($places) }}</strong> míst
            </div>
        </div>

        {{-- VYHLEDÁVÁNÍ A FILTRY KATEGORIÍ --}}
        <section class="card bg-base-100 shadow-sm border border-base-300 overflow-hidden">
            <div class="card-body p-3.5 sm:p-5 space-y-3.5">
                {{-- Search bar --}}
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-base-content/40">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Hledat kavárnu, studovnu, výhled na rande, hospodu u fakulty..."
                        class="input h-12 sm:h-14 input-bordered w-full pl-12 sm:pl-14 pr-11 bg-base-200/60 hover:bg-base-200 text-sm sm:text-base font-semibold focus:border-primary focus:bg-base-100 focus:outline-none rounded-2xl shadow-xs transition-all"
                    />
                    @if(!empty($search))
                        <button
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-base-content/40 hover:text-base-content"
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
                        <h2 class="text-sm sm:text-base font-bold flex items-center gap-2 text-base-content">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Interaktivní mapa míst pro studenty</span>
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
                                $badgeColor = match($place['category']) {
                                    'study' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
                                    'cafes' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
                                    'date' => 'bg-pink-500/10 text-pink-600 dark:text-pink-400 border-pink-500/20',
                                    'pubs' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
                                    'parks' => 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 border-cyan-500/20',
                                    default => 'bg-primary/10 text-primary border-primary/20',
                                };
                                $categoryName = $categories[$place['category']]['name'] ?? $place['category'];
                                $gMapsUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($place['name'] . ', ' . $place['address']);
                                $mapyCzUrl = "https://mapy.cz/zakladni?q=" . urlencode($place['name'] . ', ' . $place['address']);
                            @endphp
                            <div
                                class="card bg-base-100 border border-base-300 hover:border-primary/50 shadow-xs hover:shadow-md transition-all flex flex-col justify-between group"
                            >
                                <div class="card-body p-4 sm:p-5 space-y-3">
                                    {{-- Horní štítky (stručné a čisté) --}}
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border {{ $badgeColor }}">
                                            {{ $categories[$place['category']]['short_name'] ?? $place['category'] }}
                                        </span>
                                        <span class="text-[10px] font-bold text-base-content/50 uppercase tracking-wider truncate">
                                            {{ $place['highlight'] }}
                                        </span>
                                    </div>

                                    {{-- Název & adresa --}}
                                    <div>
                                        <h3
                                            @click="flyToPlace({{ $place['id'] }}, {{ $place['lat'] }}, {{ $place['lng'] }})"
                                            class="font-black text-base sm:text-lg text-base-content group-hover:text-primary transition-colors cursor-pointer"
                                        >
                                            {{ $place['name'] }}
                                        </h3>
                                        <p class="text-xs text-base-content/60 font-medium flex items-center gap-1 mt-0.5">
                                            <svg class="w-3.5 h-3.5 shrink-0 text-base-content/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            </svg>
                                            <span class="truncate">{{ $place['address'] }}</span>
                                        </p>
                                    </div>

                                    {{-- Popis --}}
                                    <p class="text-xs text-base-content/80 leading-relaxed">
                                        {{ $place['description'] }}
                                    </p>

                                    {{-- Stručné tagy (max 3 nejvýznamnější) --}}
                                    <div class="flex flex-wrap gap-1.5 pt-1">
                                        @foreach(array_slice($place['tags'], 0, 3) as $tag)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-base-200 text-base-content/70">
                                                {{ $tag }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Spodní akční tlačítka: Ukázat na mapě + Navigace (horizontálně vedle sebe, žádný overflow) --}}
                                <div class="px-4 pb-4 pt-1 sm:px-5 sm:pb-5 border-t border-base-200 flex items-center gap-2">
                                    <button
                                        type="button"
                                        @click="$dispatch('place-selected', { id: {{ $place['id'] }}, lat: {{ $place['lat'] }}, lng: {{ $place['lng'] }} })"
                                        class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 hover:border-primary flex-1 font-bold text-xs"
                                        title="Vycentrovat na mapě"
                                    >
                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        <span>Na mapě</span>
                                    </button>

                                    <a
                                        href="{{ $gMapsUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 hover:border-sky-500 text-sky-600 dark:text-sky-400 font-bold text-xs px-2 sm:px-2.5"
                                        title="Otevřít v Google Maps"
                                    >
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                                        </svg>
                                        <span>Google</span>
                                    </a>

                                    <a
                                        href="{{ $mapyCzUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 hover:border-red-500 text-red-600 dark:text-red-400 font-bold text-xs px-2 sm:px-2.5"
                                        title="Otevřít v Mapy.cz"
                                    >
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 14.93V19h-2v-2.07c-3.1-.38-5.55-2.83-5.93-5.93H3v-2h2.07c.38-3.1 2.83-5.55 5.93-5.93V3h2v2.07c3.1.38 5.55 2.83 5.93 5.93H21v2h-2.07c-.38 3.1-2.83 5.55-5.93 5.93z"/>
                                        </svg>
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
                    this.initMap();
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
                    attribution: '&copy; OpenStreetMap'
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

                (this.places || []).forEach(place => {
                    const color = categoryColors[place.category] || '#6366f1';
                    const customIcon = L.divIcon({
                        className: 'custom-student-pin',
                        html: `
                            <div style="background-color: ${color}; width: 32px; height: 32px; border-radius: 50%; border: 2.5px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center; color: white; cursor: pointer;">
                                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </div>
                        `,
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32],
                    });

                    const gMapsUrl = 'https://www.google.com/maps/search/?api=1&query=' + place.lat + ',' + place.lng;
                    const mapyCzUrl = 'https://mapy.cz/zakladni?q=' + place.lat + ',' + place.lng;

                    const popupContent = `
                        <div style="font-family: inherit; font-size: 13px; min-width: 220px; max-width: 290px; padding: 4px;">
                            <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: ${color}; letter-spacing: 0.05em; margin-bottom: 2px;">
                                ${place.highlight || 'Studentské místo'}
                            </div>
                            <h3 style="font-weight: 900; font-size: 14px; line-height: 1.25; margin-bottom: 4px; color: #0f172a;">
                                ${place.name}
                            </h3>
                            <p style="font-size: 11px; color: #64748b; margin-bottom: 6px;">
                                ${place.address}
                            </p>
                            <p style="font-size: 12px; color: #334155; line-height: 1.4; margin-bottom: 10px;">
                                ${place.description}
                            </p>
                            <div style="display: flex; gap: 6px;">
                                <a href="${gMapsUrl}" target="_blank" rel="noopener noreferrer" style="flex: 1; padding: 6px 8px; font-size: 11px; font-weight: 700; background-color: #0284c7; color: white; border-radius: 8px; text-decoration: none; text-align: center;">
                                    Google Maps
                                </a>
                                <a href="${mapyCzUrl}" target="_blank" rel="noopener noreferrer" style="flex: 1; padding: 6px 8px; font-size: 11px; font-weight: 700; background-color: #dc2626; color: white; border-radius: 8px; text-decoration: none; text-align: center;">
                                    Mapy.cz
                                </a>
                            </div>
                        </div>
                    `;

                    const marker = L.marker([place.lat, place.lng], { icon: customIcon })
                        .addTo(this.markersLayer)
                        .bindPopup(popupContent);

                    this.markers[place.id] = marker;
                    bounds.push([place.lat, place.lng]);
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
