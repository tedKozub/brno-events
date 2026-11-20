<x-layouts.app
    :title="'Vzorník 10 mapových podkladů | ' . \App\Helpers\BrandHelper::brandName()"
    :description="'Přehled a ukázky 10 volně dostupných mapových podkladů pro Brno bez nutnosti API klíčů.'"
>
    @php
    $maps = [
        [
            'id' => 'osm-standard',
            'name' => '1. OpenStreetMap Standard',
            'badge' => 'Výchozí klasika',
            'badge_color' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border-emerald-500/20',
            'provider' => 'OpenStreetMap Foundation (OSMF)',
            'desc' => 'Základní celosvětová mapa. Znázorňuje všechny ulice, pěší zóny, názvy budov i zastávky MHD.',
            'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'options' => [
                'maxZoom' => 19,
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            ],
            'tags' => ['100% Free', 'Bez API klíče', 'Vysoká stabilita']
        ],
        [
            'id' => 'osm-hot',
            'name' => '2. OpenStreetMap Humanitarian (HOT)',
            'badge' => 'Pastelové barvy',
            'badge_color' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400 border-amber-500/20',
            'provider' => 'Humanitarian OSM Team / OSM France',
            'desc' => 'Teplejší a měkčí barevné tóny. Bloky obytných budov a parky jsou lépe ohraničené a kontrastní.',
            'url' => 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
            'options' => [
                'maxZoom' => 19,
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles by HOT'
            ],
            'tags' => ['Příjemné barvy', 'Vhodné pro mapy akcí', 'Bez registrace']
        ],
        [
            'id' => 'osm-de',
            'name' => '3. OpenStreetMap Německý styl (DE)',
            'badge' => 'Střídmý & čistý',
            'badge_color' => 'bg-blue-500/10 text-blue-600 dark:text-blue-400 border-blue-500/20',
            'provider' => 'OpenStreetMap Deutschland',
            'desc' => 'Kartografický styl s tlumenými odstíny a výbornou německou typografií. Působí méně přeplácaně.',
            'url' => 'https://tile.openstreetmap.de/{z}/{x}/{y}.png',
            'options' => [
                'maxZoom' => 19,
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles by OSM DE'
            ],
            'tags' => ['Tlumené odstíny', 'Vysoká čitelnost', '100% Free']
        ],
        [
            'id' => 'osm-fr',
            'name' => '4. OpenStreetMap Francouzský styl (FR)',
            'badge' => 'Jemná grafika',
            'badge_color' => 'bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 border-indigo-500/20',
            'provider' => 'OpenStreetMap France',
            'desc' => 'Kartografie s důrazem na jemné linie, kvalitní vykreslení zeleně a parků v centru Brna.',
            'url' => 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
            'options' => [
                'maxZoom' => 20,
                'attribution' => '&copy; OpenStreetMap France | &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            ],
            'tags' => ['Detailní zeleň', 'Až zoom 20', 'Bez limitů']
        ],
        [
            'id' => 'cyclosm',
            'name' => '5. CyclOSM (Cyklo & Pěší)',
            'badge' => 'Pro studenty na kole',
            'badge_color' => 'bg-teal-500/10 text-teal-600 dark:text-teal-400 border-teal-500/20',
            'provider' => 'CyclOSM Community',
            'desc' => 'Zvýrazňuje cyklotrasy, pěší zóny, stojany na kola, schody a povrchy cest pro chůzi po městě.',
            'url' => 'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png',
            'options' => [
                'maxZoom' => 20,
                'attribution' => '&copy; CyclOSM | &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            ],
            'tags' => ['Pěší & cyklo zóny', 'Přehledné povrchy', 'Praktické pro studenty']
        ],
        [
            'id' => 'osm-dark',
            'name' => '6. OpenStreetMap Dark Mode (CSS)',
            'badge' => 'Noční režim',
            'badge_color' => 'bg-purple-500/10 text-purple-600 dark:text-purple-400 border-purple-500/20',
            'provider' => 'OSM + Invertovaný CSS filtr',
            'desc' => 'Tmavá mapa vytvořená lokálním CSS filtrem z OSM. Nevyžaduje žádné placené API a nikdy se nerozbije.',
            'url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'options' => [
                'maxZoom' => 19,
                'className' => 'map-custom-dark-tiles',
                'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            ],
            'tags' => ['Skvělý tmavý kontrast', 'Žádné cizí API klíče', 'Ideální v noci']
        ],
        [
            'id' => 'esri-streets',
            'name' => '7. ESRI World Street Map',
            'badge' => 'Moderní korporátní',
            'badge_color' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400 border-sky-500/20',
            'provider' => 'Esri / HERE, Garmin',
            'desc' => 'Moderní uliční mapa se světlými budovami, silnicemi a čistými popisky. Styl podobný velkým komerčním mapám.',
            'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
            'options' => [
                'maxZoom' => 19,
                'attribution' => 'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS'
            ],
            'tags' => ['Moderní vzhled', 'Rychlé Esri servery', 'Veřejně dostupné']
        ],
        [
            'id' => 'esri-gray',
            'name' => '8. ESRI Light Gray Canvas',
            'badge' => 'Minimalistický šedý',
            'badge_color' => 'bg-slate-500/10 text-slate-600 dark:text-slate-400 border-slate-500/20',
            'provider' => 'Esri / NAVTEQ',
            'desc' => 'Jednobarevný šedý podklad bez křiklavých rušivých prvků. Barevné špendlíky akcí na něm okamžitě září.',
            'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
            'options' => [
                'maxZoom' => 16,
                'attribution' => 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ'
            ],
            'tags' => ['Extrémní kontrast pro body', 'Čistý minimalismus', 'Designový look']
        ],
        [
            'id' => 'esri-topo',
            'name' => '9. ESRI World Topo Map',
            'badge' => 'Plastický terén',
            'badge_color' => 'bg-lime-500/10 text-lime-600 dark:text-lime-400 border-lime-500/20',
            'provider' => 'Esri / TomTom, USGS',
            'desc' => 'Topografická mapa se stínováním reliéfu kopců a parků. Skvěle ukazuje terén kolem Špilberku i Kraví hory.',
            'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
            'options' => [
                'maxZoom' => 19,
                'attribution' => 'Tiles &copy; Esri &mdash; Source: Esri, TomTom, DigitalGlobe'
            ],
            'tags' => ['Vrstevnice a reliéf', 'Přírodní parky', 'Tradiční topografie']
        ],
        [
            'id' => 'esri-imagery',
            'name' => '10. ESRI World Imagery (Satelitní)',
            'badge' => 'Letecké snímky',
            'badge_color' => 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border-rose-500/20',
            'provider' => 'Esri / Maxar, Earthstar Geographics',
            'desc' => 'Reálné satelitní a letecké snímky Brna ve vysokém rozlišení. Přesně vidíš střechy budov, nádvoří a parky.',
            'url' => 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            'options' => [
                'maxZoom' => 18,
                'attribution' => 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics'
            ],
            'tags' => ['Vysoké rozlišení', 'Fotorealistický pohled', 'Bez nutnosti tokenu']
        ],
    ];
    @endphp

    <style>
        .map-custom-dark-tiles {
            filter: invert(100%) hue-rotate(180deg) brightness(92%) contrast(92%) !important;
        }
        .showcase-pin {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 9999px;
            color: #ffffff;
            font-weight: 800;
            font-size: 13px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.35);
            border: 2px solid #ffffff;
        }
    </style>

    <div class="min-h-screen bg-base-100 py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <a href="/" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline mb-3">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Zpět na přehled akcí
                </a>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-black text-base-content tracking-tight">
                            Vzorník 10 volně dostupných mapových podkladů
                        </h1>
                        <p class="text-sm text-base-content/70 mt-1 max-w-3xl">
                            Každá mapa níže je plně interaktivní, vycentrovaná na centrum Brna (Náměstí Svobody, Špilberk, Moravské náměstí) a funguje <strong class="text-base-content font-bold">100% bez API klíčů</strong> a bez rizika výpadků nebo vodoznaků.
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            10 živých ukázek
                        </span>
                    </div>
                </div>
            </div>

            <!-- 10 Map Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($maps as $index => $m)
                    <div class="bg-base-200/60 dark:bg-base-200/40 border border-base-300 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow flex flex-col justify-between">
                        <!-- Card Header -->
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-2">
                                <div>
                                    <h2 class="text-lg font-bold text-base-content">
                                        {{ $m['name'] }}
                                    </h2>
                                    <span class="text-xs text-base-content/60 font-medium">
                                        Zdroj: {{ $m['provider'] }}
                                    </span>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold border {{ $m['badge_color'] }} shrink-0">
                                    {{ $m['badge'] }}
                                </span>
                            </div>

                            <p class="text-xs sm:text-sm text-base-content/80 mb-3 leading-relaxed">
                                {{ $m['desc'] }}
                            </p>

                            <div class="flex flex-wrap gap-1.5 mb-3">
                                @foreach($m['tags'] as $tag)
                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-base-300/80 text-base-content/75">
                                        {{ $tag }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Map Canvas Container -->
                        <div class="relative w-full rounded-xl overflow-hidden border border-base-300 bg-base-300/40 my-2">
                            <div id="map-preview-slot-{{ $index }}" style="height: 320px; width: 100%; z-index: 10;"></div>
                            <div id="map-preview-loading-{{ $index }}" class="absolute inset-0 flex items-center justify-center bg-base-200/80 text-xs font-semibold text-base-content/60 pointer-events-none">
                                Načítám mapu...
                            </div>
                        </div>

                        <!-- Card Footer / Technical Specs -->
                        <div class="pt-2 text-[11px] text-base-content/60 flex items-center justify-between font-mono">
                            <span class="truncate max-w-[260px] sm:max-w-md" title="{{ $m['url'] }}">
                                {{ $m['url'] }}
                            </span>
                            <span class="shrink-0 ml-2 font-semibold">
                                Max Zoom: {{ $m['options']['maxZoom'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Standalone Map Loader Script (No dependencies, No Alpine coupling) -->
    <script>
        (function() {
            var mapsConfig = @json($maps);

            function initAllMaps() {
                if (typeof L === 'undefined') {
                    setTimeout(initAllMaps, 100);
                    return;
                }

                // Sample points in Brno
                var points = [
                    { lat: 49.1951, lng: 16.6068, title: 'Náměstí Svobody', color: '#e11d48', label: '1' },
                    { lat: 49.1944, lng: 16.5997, title: 'Hrad Špilberk', color: '#2563eb', label: '2' },
                    { lat: 49.1985, lng: 16.6080, title: 'Moravské náměstí', color: '#16a34a', label: '3' }
                ];

                mapsConfig.forEach(function(cfg, idx) {
                    var containerId = 'map-preview-slot-' + idx;
                    var container = document.getElementById(containerId);
                    var loadingEl = document.getElementById('map-preview-loading-' + idx);
                    if (!container) return;

                    // Prevent re-initialization if already initialized
                    if (container._leaflet_id) return;

                    try {
                        var map = L.map(containerId, {
                            center: [49.1951, 16.6068],
                            zoom: 14,
                            scrollWheelZoom: true,
                            attributionControl: true
                        });

                        L.tileLayer(cfg.url, cfg.options).addTo(map);

                        // Add sample markers
                        points.forEach(function(pt) {
                            var icon = L.divIcon({
                                className: '',
                                html: '<div class="showcase-pin" style="background-color: ' + pt.color + ';">' + pt.label + '</div>',
                                iconSize: [32, 32],
                                iconAnchor: [16, 16],
                                popupAnchor: [0, -16]
                            });

                            L.marker([pt.lat, pt.lng], { icon: icon })
                                .addTo(map)
                                .bindPopup('<strong>' + pt.title + '</strong><br><span style="font-size: 11px; color: #666;">Brno</span>');
                        });

                        if (loadingEl) {
                            loadingEl.style.display = 'none';
                        }

                        // Force tile recalculation once container is visible
                        setTimeout(function() {
                            map.invalidateSize();
                        }, 250);

                    } catch (e) {
                        console.error('Failed to init map #' + idx, e);
                        if (loadingEl) {
                            loadingEl.textContent = 'Chyba načtení mapy: ' + e.message;
                        }
                    }
                });
            }

            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                setTimeout(initAllMaps, 50);
            } else {
                window.addEventListener('DOMContentLoaded', initAllMaps);
                window.addEventListener('load', initAllMaps);
            }
        })();
    </script>
</x-layouts.app>
