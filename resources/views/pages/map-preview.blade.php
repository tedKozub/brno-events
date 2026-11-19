<x-layouts.app
    :title="'Výběr mapového podkladu | ' . \App\Helpers\BrandHelper::brandName()"
    :description="'Vyber si nový vzhled mapy pro BrnoAkce a studyBrno. Každý styl má živou ukázku a můžeš ho jedním kliknutím aktivovat pro celý web.'"
>
    {{-- HORNÍ HLAVIČKA S NAVIGACÍ --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20 gap-4">
                <div class="flex items-center gap-3">
                    <a href="/" wire:navigate class="flex items-center gap-2.5 group">
                        <div>
                            <span class="font-black text-xl tracking-tight text-base-content block leading-tight">{!! \App\Helpers\BrandHelper::logoHtml() !!}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 block">Nastavení vzhledu mapy</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="/" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span>Hlavní web</span>
                    </a>

                    <a href="/mista" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content hidden sm:inline-flex">
                        Studentská místa
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- HLAVNÍ OBSAH --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-8" x-data="mapChooser()">
        {{-- Intro hlavička --}}
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 pb-6 border-b border-base-300">
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    <span>Volně dostupné mapové podklady · Živé náhledy</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-black text-base-content tracking-tight">
                    Vyber si nový vzhled mapy pro Brno
                </h1>

                <p class="text-sm sm:text-base text-base-content/70 max-w-3xl leading-relaxed">
                    Níže vidíš <strong>reálné živé náhledy mapy Brna</strong> pro jednotlivé volně dostupné podklady. Kliknutím na <strong>„Použít pro celý web“</strong> se vybraný styl okamžitě uloží a aplikuje na nástěnku akcí i mapu studentských míst.
                </p>
            </div>

            {{-- Globální stav & Tlačítko na přepnutí motivu webu --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
                <button 
                    @click="toggleTheme()" 
                    class="btn btn-sm btn-outline gap-2 font-bold rounded-xl"
                    title="Přepnout světlý / tmavý režim celého webu"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    <span>Přepnout motiv (Light/Dark)</span>
                </button>
            </div>
        </div>

        {{-- AKTIVNÍ REŽIM BANNER --}}
        <div class="card bg-base-100 border-2 border-primary/30 shadow-md p-4 sm:p-6 rounded-2xl flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black text-xl shadow-xs shrink-0">
                    🗺️
                </div>
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-base-content/50 block">Právě aktivní styl na webu:</span>
                    <h2 class="text-xl font-black text-base-content tracking-tight" x-text="getActiveStyleTitle()"></h2>
                    <p class="text-xs text-base-content/70 mt-0.5" x-text="getActiveStyleSubtitle()"></p>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <button 
                    @click="applyStyle('auto')" 
                    :class="activeStyleId === 'auto' ? 'btn-primary' : 'btn-outline'"
                    class="btn btn-sm rounded-xl font-bold gap-1.5"
                >
                    <span x-show="activeStyleId === 'auto'">✓</span>
                    <span>Automaticky (Auto Light/Dark)</span>
                </button>

                <a href="/" wire:navigate class="btn btn-sm btn-ghost border border-base-300 rounded-xl font-bold gap-1">
                    <span>Otevřít akce</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
            </div>
        </div>

        {{-- OZNÁMENÍ O ZMĚNĚ (TOAST) --}}
        <div 
            x-show="showToast" 
            x-transition 
            class="alert alert-success shadow-lg rounded-2xl font-bold text-sm flex justify-between items-center"
            style="display: none;"
        >
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span x-text="toastMessage"></span>
            </div>
            <a href="/" wire:navigate class="btn btn-xs btn-neutral rounded-lg">Vyzkoušet na nástěnce</a>
        </div>

        {{-- MŘÍŽKA KARET S JEDNOTLIVÝMI MAPAMI --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="item in styleList" :key="item.id">
                <div 
                    class="card bg-base-100 border transition-all duration-200 rounded-2xl overflow-hidden shadow-xs hover:shadow-lg flex flex-col justify-between"
                    :class="activeStyleId === item.id ? 'border-primary ring-2 ring-primary/30 shadow-md' : 'border-base-300'"
                >
                    <div>
                        {{-- HLAVIČKA KARTY --}}
                        <div class="p-4 border-b border-base-200 flex items-center justify-between gap-2 bg-base-200/40">
                            <div>
                                <h3 class="font-black text-base text-base-content tracking-tight" x-text="item.name"></h3>
                                <span class="text-[11px] text-base-content/50 font-mono block" x-text="item.provider"></span>
                            </div>
                            <div>
                                <template x-if="activeStyleId === item.id">
                                    <span class="badge badge-primary font-bold text-xs gap-1 py-2.5 px-2.5">
                                        <span>✓</span> Aktivní
                                    </span>
                                </template>
                                <template x-if="activeStyleId !== item.id && item.badge">
                                    <span class="badge badge-accent badge-outline font-bold text-xs" x-text="item.badge"></span>
                                </template>
                            </div>
                        </div>

                        {{-- SAMOTNÁ ŽIVÁ MAPA V KARTĚ --}}
                        <div class="relative w-full h-64 bg-base-300 border-b border-base-300 overflow-hidden">
                            <div :id="'map-slot-' + item.id" class="w-full h-full z-10"></div>
                            
                            {{-- Info popisek v rohu mapy --}}
                            <div class="absolute bottom-2 left-2 z-[400] bg-base-100/90 backdrop-blur-md px-2 py-1 rounded-md text-[10px] font-bold text-base-content/70 shadow-xs border border-base-300/60 pointer-events-none">
                                Brno centrum (Svoboďák & Česká)
                            </div>
                        </div>

                        {{-- POPIS A VÝHODY --}}
                        <div class="p-4 space-y-2.5">
                            <p class="text-xs text-base-content/75 leading-relaxed" x-text="item.description"></p>
                            
                            <div class="text-[11px] space-y-1 pt-2 border-t border-base-200">
                                <div class="flex items-start gap-1.5 text-success">
                                    <span class="font-bold">✓</span>
                                    <span class="text-base-content/80 font-medium" x-text="item.pros"></span>
                                </div>
                                <div class="flex items-start gap-1.5 text-base-content/60">
                                    <span class="font-bold">•</span>
                                    <span x-text="item.note"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SPODNÍ TLAČÍTKO PRO AKTIVACI --}}
                    <div class="p-4 pt-0">
                        <button 
                            @click="applyStyle(item.id)" 
                            :class="activeStyleId === item.id ? 'btn-primary' : 'btn-outline border-base-300 hover:btn-primary'"
                            class="btn btn-sm w-full rounded-xl font-bold gap-2 transition-all"
                        >
                            <template x-if="activeStyleId === item.id">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                    <span>Tento styl je aktivní</span>
                                </span>
                            </template>
                            <template x-if="activeStyleId !== item.id">
                                <span>Použít tento podklad pro web</span>
                            </template>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </main>

    <x-footer />

    {{-- JAVASCRIPT INICIALIZACE VŠECH ŽIVÝCH NÁHLEDŮ --}}
    <script>
        function mapChooser() {
            return {
                activeStyleId: localStorage.getItem('brno_events_map_style') || 'auto',
                showToast: false,
                toastMessage: '',
                maps: {},

                styleList: [
                    {
                        id: 'carto-positron',
                        name: 'CartoDB Positron',
                        provider: 'CARTO / OpenStreetMap',
                        badge: '🥇 Doporučeno pro den',
                        description: 'Ultra čistý, moderní světlý podklad. Šedobílé pozadí potlačuje rušivou uliční síť a dává 100% prostor barevným špendlíkům akcí.',
                        pros: 'Minimální vizuální šum, špičkový kontrast pro barevné ikony.',
                        note: 'Rychlé celosvětové CDN, zdarma a bez API klíče.'
                    },
                    {
                        id: 'carto-dark',
                        name: 'CartoDB Dark Matter',
                        provider: 'CARTO / OpenStreetMap',
                        badge: '🥇 Doporučeno pro noc',
                        description: 'Sytý, elegantní tmavý podklad. Silnice tvoří jemné linky a špendlíky doslova září jako neonové body.',
                        pros: 'Skvělý noční zážitek, neunavuje oči při večerním hledání akcí.',
                        note: 'Ideální kombinace s Dark mode motivem webu.'
                    },
                    {
                        id: 'carto-voyager',
                        name: 'CartoDB Voyager',
                        provider: 'CARTO / OpenStreetMap',
                        badge: '🥈 Hravý & Barevný',
                        description: 'Moderní podklad s příjemnými pastelovými barvami parků (Lužánky, Špilberk), vody a zřetelnými názvy ulic.',
                        pros: 'Velmi přátelský a čitelný vzhled studentského průvodce.',
                        note: 'Zachovává čistotu vektorového tisku bez přeplácanosti.'
                    },
                    {
                        id: 'osm-standard',
                        name: 'OpenStreetMap Standard',
                        provider: 'OpenStreetMap Foundation',
                        badge: 'Současný stav',
                        description: 'Původní základní styl OpenStreetMap se všemi popisnými čísly, zastávkami a ikonami obchodů.',
                        pros: 'Klasika, kterou každý zná.',
                        note: 'Při velkém počtu pinů může působit přeplněně.'
                    },
                    {
                        id: 'osm-hot',
                        name: 'OSM Humanitarian (HOT)',
                        provider: 'HOT / OSM France',
                        badge: 'Měkké tóny',
                        description: 'Pastelovější verze OpenStreetMap s teplými tóny a výraznějším odlišením jednotlivých bloků budov.',
                        pros: 'Měkčí a teplejší barvy než OSM Standard.',
                        note: 'Vhodné pro přehled městské zástavby.'
                    },
                    {
                        id: 'cyclosm',
                        name: 'CyclOSM (Cyklo & Chůze)',
                        provider: 'CyclOSM France',
                        badge: 'Pro koloběžky & kola',
                        description: 'Podklad zaměřený na městský pohyb – zvýrazňuje cyklostezky, zóny pro pěší, stojany na kola a reliéf.',
                        pros: 'Perfektní pro studenty pohybující se po Brně na sdílených kolech.',
                        note: 'Barevně odlišuje typy povrchů cest.'
                    },
                    {
                        id: 'esri-gray',
                        name: 'ESRI Light Gray Canvas',
                        provider: 'Esri / HERE',
                        badge: 'Monochrom',
                        description: 'Čisté šedotónové plátno navržené pro datovou vizualizaci. Mapa ustupuje úplně do pozadí.',
                        pros: 'Absolutní priorita pro data a piny.',
                        note: 'Max zoom 16 (méně podrobností při maximálním přiblížení).'
                    },
                    {
                        id: 'esri-topo',
                        name: 'ESRI World Topo',
                        provider: 'Esri / TomTom',
                        badge: 'Topografická',
                        description: 'Plastická mapa s prostorovým stínováním kopců a parků (Petrov, Kraví hora, Černá Pole).',
                        pros: 'Přirozený atlasový vjem terénu Brna.',
                        note: 'Tradiční geografický vzhled.'
                    },
                    {
                        id: 'esri-imagery',
                        name: 'ESRI Satelitní snímky',
                        provider: 'Esri / Maxar',
                        badge: 'Letecký pohled',
                        description: 'Reálné letecké a satelitní snímky budov, dvorů a areálů univerzit z ptačí perspektivy.',
                        pros: 'Vidíš skutečnou budovu a její okolí.',
                        note: 'Špendlíky potřebují bílé ohraničení pro dobrý kontrast.'
                    }
                ],

                samplePins: [
                    { name: 'Náměstí Svobody (Orloj)', lat: 49.1951, lng: 16.6068, color: '#ec4899' },
                    { name: 'Česká (Hodiny)', lat: 49.1973, lng: 16.6060, color: '#3b82f6' },
                    { name: 'Hrad Špilberk', lat: 49.1946, lng: 16.6000, color: '#10b981' }
                ],

                init() {
                    // Počkáme na načtení DOM a inicializujeme všech 9 map
                    this.$nextTick(() => {
                        this.initAllMaps();
                    });
                },

                getActiveStyleTitle() {
                    if (this.activeStyleId === 'auto') {
                        return 'Automatický režim (Podle motivu webu)';
                    }
                    const found = this.styleList.find(s => s.id === this.activeStyleId);
                    return found ? found.name : 'CartoDB Positron';
                },

                getActiveStyleSubtitle() {
                    if (this.activeStyleId === 'auto') {
                        return 'Ve světlém režimu se používá CartoDB Positron, v tmavém CartoDB Dark Matter.';
                    }
                    return 'Tento podklad se nyní zobrazuje na hlavní stránce akcí i studentských místech.';
                },

                initAllMaps() {
                    if (typeof L === 'undefined') {
                        setTimeout(() => this.initAllMaps(), 100);
                        return;
                    }

                    this.styleList.forEach(style => {
                        const containerId = 'map-slot-' + style.id;
                        const el = document.getElementById(containerId);
                        if (!el || this.maps[style.id]) return;

                        try {
                            const map = L.map(containerId, {
                                zoomControl: false,
                                attributionControl: false,
                                scrollWheelZoom: false,
                                dragging: true,
                                touchZoom: true,
                                doubleClickZoom: false
                            }).setView([49.1958, 16.6055], 14);

                            // Načteme konfiguraci z window.BRNO_MAP_STYLES
                            const cfg = window.BRNO_MAP_STYLES[style.id];
                            if (cfg) {
                                L.tileLayer(cfg.url, cfg.options).addTo(map);
                            }

                            // Přidáme ukázkové špendlíky
                            this.samplePins.forEach(p => {
                                const pinHtml = `
                                    <div style="background-color: ${p.color}; width: 28px; height: 28px; border-radius: 50%; border: 2px solid #ffffff; box-shadow: 0 3px 8px rgba(0,0,0,0.35); display: flex; align-items: center; justify-content: center;">
                                        <svg style="width: 14px; height: 14px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        </svg>
                                    </div>
                                `;
                                const icon = L.divIcon({
                                    className: 'custom-preview-pin',
                                    html: pinHtml,
                                    iconSize: [28, 28],
                                    iconAnchor: [14, 28]
                                });
                                L.marker([p.lat, p.lng], { icon: icon }).addTo(map);
                            });

                            this.maps[style.id] = map;

                            setTimeout(() => {
                                map.invalidateSize();
                            }, 200);
                        } catch (err) {
                            console.error('Chyba při inicializaci mapy ' + style.id, err);
                        }
                    });
                },

                applyStyle(styleId) {
                    this.activeStyleId = styleId;
                    localStorage.setItem('brno_events_map_style', styleId);

                    const title = this.getActiveStyleTitle();
                    this.toastMessage = `Styl „${title}“ byl úspěšně aktivován pro celý web!`;
                    this.showToast = true;

                    setTimeout(() => {
                        this.showToast = false;
                    }, 4000);
                },

                toggleTheme() {
                    const current = document.documentElement.getAttribute('data-theme') || 'events-light';
                    const next = current === 'events-dark' ? 'events-light' : 'events-dark';
                    document.documentElement.setAttribute('data-theme', next);
                    localStorage.setItem('brno_events_theme', next);

                    if (next === 'events-dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            };
        }
    </script>

    <style>
        .custom-preview-pin {
            background: transparent;
            border: none;
        }
    </style>
</x-layouts.app>
