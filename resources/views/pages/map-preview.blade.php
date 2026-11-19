<x-layouts.app
    :title="'Vzorník mapových podkladů | ' . \App\Helpers\BrandHelper::brandName()"
    :description="'Interaktivní srovnávač a vzorník volně dostupných mapových podkladů pro studentskou mapu Brna. Vyberte nový vizuální styl.'"
>
    {{-- HORNÍ HLAVIČKA S NAVIGACÍ --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20 gap-4">
                <div class="flex items-center gap-3">
                    <a href="/" wire:navigate class="flex items-center gap-2.5 group">
                        <div>
                            <span class="font-black text-xl tracking-tight text-base-content block leading-tight">{!! \App\Helpers\BrandHelper::logoHtml() !!}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-base-content/50 block">Výběr mapového stylu</span>
                        </div>
                    </a>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="/" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        <span class="hidden sm:inline">Zpět na přehled akcí</span>
                        <span class="sm:hidden">Zpět</span>
                    </a>

                    <a href="/mista" wire:navigate class="btn btn-sm btn-ghost gap-1.5 font-bold rounded-xl text-base-content/80 hover:text-base-content hidden sm:inline-flex">
                        Studentská místa
                    </a>
                </div>
            </div>
        </div>
    </header>

    {{-- HLAVNÍ OBSAH --}}
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 space-y-10" x-data="mapStyleSelector()">
        {{-- Intro hlavička --}}
        <div class="space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-primary/10 text-primary text-xs font-bold uppercase tracking-wider">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                <span>Leaflet Tile Providers · Look & Feel Playground</span>
            </div>
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl sm:text-4xl font-black text-base-content tracking-tight">
                        Vzorník mapových podkladů pro Brno
                    </h1>
                    <p class="text-sm sm:text-base text-base-content/70 mt-2 max-w-3xl leading-relaxed">
                        Všechny níže uvedené podklady jsou <strong>100% volně dostupné bez nutnosti registrace či API klíče</strong>. Vyzkoušej si, jak na jednotlivých podkladech vyniknou barevné špendlíky studentských akcí a klubů v Brně.
                    </p>
                </div>

                {{-- Tlačítko pro rychlé přepnutí Light / Dark režimu celého webu --}}
                <div class="flex items-center gap-2 shrink-0">
                    <button 
                        @click="toggleTheme()" 
                        class="btn btn-sm btn-outline gap-2 font-bold rounded-xl"
                        title="Přepnout motiv webu (světlý / tmavý)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <span>Změnit motiv webu</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- HLAVNÍ INTERAKTIVNÍ TESTOVACÍ MAPA --}}
        <section class="card bg-base-100 border border-base-300 shadow-xl overflow-hidden rounded-3xl">
            <div class="p-4 sm:p-6 border-b border-base-300 bg-base-200/50 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold uppercase tracking-wider text-base-content/50">Aktivní styl:</span>
                        <span class="badge badge-primary font-bold text-sm px-3 py-3" x-text="currentStyle.name"></span>
                        <template x-if="currentStyle.recommended">
                            <span class="badge badge-accent badge-sm font-bold" x-text="currentStyle.recommended"></span>
                        </template>
                    </div>
                    <p class="text-xs sm:text-sm text-base-content/70 mt-1" x-text="currentStyle.description"></p>
                </div>

                {{-- Přepínač na horní liště --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    <template x-for="style in styles" :key="style.id">
                        <button 
                            @click="selectStyle(style.id)"
                            :class="activeStyleId === style.id ? 'btn-primary shadow-sm' : 'btn-ghost text-base-content/70'"
                            class="btn btn-xs sm:btn-sm rounded-xl font-bold transition-all"
                            x-text="style.shortName"
                        ></button>
                    </template>
                </div>
            </div>

            {{-- Kontejner mapy --}}
            <div class="relative w-full h-[450px] sm:h-[550px] z-10">
                <div id="main-preview-map" class="w-full h-full"></div>

                {{-- Legenda špendlíků v pravém horním rohu --}}
                <div class="absolute top-3 right-3 z-[1000] bg-base-100/90 backdrop-blur-md border border-base-300 rounded-2xl p-3 shadow-lg text-xs max-w-xs hidden sm:block">
                    <span class="font-bold text-base-content block mb-2">Ukázkové špendlíky:</span>
                    <div class="space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-pink-500 shrink-0"></span>
                            <span class="text-base-content/80">Párty & Kluby (Fléda, ArtBar, Koma)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500 shrink-0"></span>
                            <span class="text-base-content/80">Fakulty MUNI (FF, FSS, Kotlářská)</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 shrink-0"></span>
                            <span class="text-base-content/80">MENDELU & Vinařská</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-amber-500 shrink-0"></span>
                            <span class="text-base-content/80">Parky & Kino Scala</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kód a parametry pro implementaci --}}
            <div class="p-4 sm:p-6 bg-base-200/30 border-t border-base-300">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        <span class="text-xs font-mono font-bold uppercase tracking-wider text-base-content/70">Leaflet konfigurace pro tento styl</span>
                    </div>
                    <button 
                        @click="copySnippet()" 
                        class="btn btn-xs btn-outline gap-1.5 rounded-lg"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                        <span x-text="copied ? 'Zkopírováno!' : 'Zkopírovat kód'"></span>
                    </button>
                </div>
                <pre class="bg-base-300/60 p-3 sm:p-4 rounded-xl text-[11px] sm:text-xs font-mono overflow-x-auto text-base-content/90 border border-base-300" x-text="currentStyle.codeSnippet"></pre>
            </div>
        </section>

        {{-- SROVNÁVACÍ KARTY JEDNOTLIVÝCH STYLŮ --}}
        <section class="space-y-6">
            <div>
                <h2 class="text-2xl font-black text-base-content tracking-tight">Katalog mapových podkladů & doporučení</h2>
                <p class="text-sm text-base-content/70 mt-1">Porovnání 9 bezplatných dlaždicových vrstev s výhodami a nevýhodami pro studentský web.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="style in styles" :key="style.id">
                    <div 
                        class="card bg-base-100 border transition-all duration-200 hover:shadow-lg rounded-2xl overflow-hidden flex flex-col justify-between"
                        :class="activeStyleId === style.id ? 'border-primary ring-2 ring-primary/20 shadow-md' : 'border-base-300 hover:border-base-content/20'"
                    >
                        <div>
                            {{-- Mini náhled mapy --}}
                            <div class="relative h-44 w-full bg-base-200 border-b border-base-300 overflow-hidden">
                                <div :id="'mini-map-' + style.id" class="w-full h-full pointer-events-none"></div>
                                <div class="absolute top-2 left-2 z-[500] flex gap-1 flex-wrap">
                                    <template x-if="style.recommended">
                                        <span class="badge badge-accent badge-xs sm:badge-sm font-bold shadow-xs" x-text="style.recommended"></span>
                                    </template>
                                    <template x-if="style.id === 'osm-standard'">
                                        <span class="badge badge-neutral badge-xs sm:badge-sm font-bold shadow-xs">Současný stav</span>
                                    </template>
                                </div>
                            </div>

                            <div class="p-5 space-y-3">
                                <div>
                                    <h3 class="font-bold text-lg text-base-content tracking-tight" x-text="style.name"></h3>
                                    <span class="text-xs font-mono text-base-content/50 block mt-0.5" x-text="style.provider"></span>
                                </div>

                                <p class="text-xs text-base-content/70 leading-relaxed" x-text="style.description"></p>

                                {{-- Výhody / Vlastnosti --}}
                                <div class="space-y-1.5 pt-2 border-t border-base-200 text-xs">
                                    <div class="flex items-start gap-2">
                                        <span class="text-success font-bold">✓</span>
                                        <span class="text-base-content/80" x-text="style.pros"></span>
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <span class="text-base-content/40 font-bold">•</span>
                                        <span class="text-base-content/60" x-text="style.note"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-5 pt-0">
                            <button 
                                @click="selectStyle(style.id); window.scrollTo({top: 150, behavior: 'smooth'})"
                                :class="activeStyleId === style.id ? 'btn-primary' : 'btn-outline'"
                                class="btn btn-sm w-full rounded-xl font-bold gap-2"
                            >
                                <span x-text="activeStyleId === style.id ? 'Právě zobrazeno' : 'Vyzkoušet na hlavní mapě'"></span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        {{-- DOPORUČENÍ PRO PRODUKCI --}}
        <section class="card bg-gradient-to-br from-primary/10 via-base-100 to-secondary/10 border border-primary/20 p-6 sm:p-8 rounded-3xl space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-primary text-primary-content flex items-center justify-center font-black text-lg shadow-sm">
                    💡
                </div>
                <div>
                    <h3 class="text-lg sm:text-xl font-black text-base-content tracking-tight">
                        Doporučení pro web BrnoAkce / studyBrno
                    </h3>
                    <p class="text-xs sm:text-sm text-base-content/70">
                        Jaký styl vybrat a jak vytěžit maximum z Leafletu?
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs sm:text-sm text-base-content/80 leading-relaxed">
                <div class="bg-base-100/80 p-4 rounded-2xl border border-base-300/80 space-y-2">
                    <span class="font-bold text-primary flex items-center gap-1.5 text-sm">
                        <span>🥇</span> Nejlepší volba: Automatické přepínání (Positron + Dark Matter)
                    </span>
                    <p>
                        Stávající <strong>OSM Standard</strong> obsahuje příliš mnoho drobných detailů, domovních čísel a zastávek, které vizuálně přebíjejí akce.
                    </p>
                    <p>
                        Nasazením <strong>CartoDB Positron</strong> ve světlém režimu a <strong>CartoDB Dark Matter</strong> v tmavém režimu dosáhneme naprosto prémiového vzhledu: podklad ustoupí do pozadí a barevné špendlíky akcí (růžové párty, modré přednášky, zelené sporty) okamžitě upoutají pozornost.
                    </p>
                </div>

                <div class="bg-base-100/80 p-4 rounded-2xl border border-base-300/80 space-y-2">
                    <span class="font-bold text-secondary flex items-center gap-1.5 text-sm">
                        <span>🥈</span> Hravá alternativa: CartoDB Voyager
                    </span>
                    <p>
                        Pokud chceš, aby mapa působila živěji a studenti snadno poznali park Lužánky, Kraví horu či Špilberk díky jemné zelené a vodním plochám, <strong>CartoDB Voyager</strong> je nejlepší volbou.
                    </p>
                    <p>
                        Ponechává si čistotu moderní vektorové kartografie, ale má příjemné pastelové barvy přírody a městských zón.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <x-footer />

    {{-- INICIALIZACE A LOGIKA PŘEPÍNAČE --}}
    <script>
        function mapStyleSelector() {
            return {
                mainMap: null,
                mainTileLayer: null,
                activeStyleId: 'carto-positron',
                copied: false,
                miniMaps: {},

                styles: [
                    {
                        id: 'carto-positron',
                        name: 'CartoDB Positron',
                        shortName: 'Positron (Light)',
                        provider: 'CARTO / OpenStreetMap',
                        recommended: 'Doporučeno pro Light mode',
                        url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                        maxZoom: 20,
                        subdomains: 'abcd',
                        description: 'Ultra čistý, minimalistický světlý podklad. Špendlíky a kategorie akcí maximálně vyniknou.',
                        pros: 'Minimální vizuální šum, špičkový kontrast pro barevné piny.',
                        note: 'Celosvětové CDN, 100% free bez API klíče.',
                        codeSnippet: `L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 20,
    subdomains: 'abcd',
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
}).addTo(map);`
                    },
                    {
                        id: 'carto-dark',
                        name: 'CartoDB Dark Matter',
                        shortName: 'Dark Matter (Dark)',
                        provider: 'CARTO / OpenStreetMap',
                        recommended: 'Doporučeno pro Dark mode',
                        url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                        maxZoom: 20,
                        subdomains: 'abcd',
                        description: 'Elegantní tmavý noční podklad s jemnými linkami silnic. Dokonalý soulad s dark mode a večírky.',
                        pros: 'Krásný noční kontrast, neoslňuje při večerním prohlížení.',
                        note: 'Celosvětové CDN, 100% free bez API klíče.',
                        codeSnippet: `L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    maxZoom: 20,
    subdomains: 'abcd',
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
}).addTo(map);`
                    },
                    {
                        id: 'carto-voyager',
                        name: 'CartoDB Voyager',
                        shortName: 'Voyager (Barevný)',
                        provider: 'CARTO / OpenStreetMap',
                        recommended: 'Barevný & Přátelský',
                        url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                        maxZoom: 20,
                        subdomains: 'abcd',
                        description: 'Moderní, vizuálně hřejivý podklad s příjemnými barvami parků, vody a čitelnými názvy čtvrtí.',
                        pros: 'Příjemné pastelové barvy parků, velmi čitelná uliční síť.',
                        note: 'Ideální pro studentský městský magazín a průvodce.',
                        codeSnippet: `L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    maxZoom: 20,
    subdomains: 'abcd',
    attribution: '&copy; OpenStreetMap contributors &copy; CARTO'
}).addTo(map);`
                    },
                    {
                        id: 'osm-standard',
                        name: 'OpenStreetMap Standard',
                        shortName: 'OSM Standard',
                        provider: 'OpenStreetMap Foundation',
                        recommended: null,
                        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                        maxZoom: 19,
                        subdomains: 'abc',
                        description: 'Klasický základní vzhled OpenStreetMap s plnými detaily budov, čísel popisných a zastávek.',
                        pros: 'Standardní mapový podklad, všem důvěrně známý.',
                        note: 'Při větším množství pinů působí mapa přeplácaně.',
                        codeSnippet: `L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);`
                    },
                    {
                        id: 'osm-hot',
                        name: 'OSM Humanitarian (HOT)',
                        shortName: 'OSM HOT',
                        provider: 'HOT / OpenStreetMap France',
                        recommended: 'Měkké tóny',
                        url: 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png',
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Tiles style by HOT',
                        maxZoom: 19,
                        subdomains: 'abc',
                        description: 'Humanitární styl s jemnějšími pastelovými odstíny a výraznějším odlišením budov.',
                        pros: 'Měkčí barvy než OSM Standard, dobrá čitelnost terénu.',
                        note: 'Provozováno francouzskou komunitou OSM.',
                        codeSnippet: `L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors, Tiles style by HOT'
}).addTo(map);`
                    },
                    {
                        id: 'cyclosm',
                        name: 'CyclOSM',
                        shortName: 'CyclOSM (Cyklo)',
                        provider: 'CyclOSM / OpenStreetMap France',
                        recommended: 'Pro koloběžky & kola',
                        url: 'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png',
                        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors. Tiles by CyclOSM',
                        maxZoom: 20,
                        subdomains: 'abc',
                        description: 'Specializovaná městská mapa zvýrazňující cyklostezky, zóny pro pěší, stojany na kola a reliéf.',
                        pros: 'Perfektní pro studenty na kolech a sdílených koloběžkách.',
                        note: 'Specifický vizuální styl se zaměřením na mobilitu.',
                        codeSnippet: `L.tileLayer('https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', {
    maxZoom: 20,
    attribution: '&copy; OpenStreetMap contributors. Tiles by CyclOSM'
}).addTo(map);`
                    },
                    {
                        id: 'esri-gray',
                        name: 'ESRI Light Gray Canvas',
                        shortName: 'ESRI Gray',
                        provider: 'Esri / HERE, Garmin',
                        recommended: 'Monochrom',
                        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}',
                        attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ',
                        maxZoom: 16,
                        subdomains: '',
                        description: 'Čistý šedotónový podklad navržený přímo pro infografiky a tematické mapy s vysokým kontrastem dat.',
                        pros: 'Extrémní čistota, mapa je doslova plátnem pro vaše body.',
                        note: 'Maximální zoom je 16 (méně detailní domy při hlubokém zoomu).',
                        codeSnippet: `L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 16,
    attribution: 'Tiles &copy; Esri'
}).addTo(map);`
                    },
                    {
                        id: 'esri-topo',
                        name: 'ESRI World Topo',
                        shortName: 'ESRI Topo',
                        provider: 'Esri / USGS, FAO, NPS',
                        recommended: 'Topografická',
                        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}',
                        attribution: 'Tiles &copy; Esri &mdash; Esri, DeLorme, NAVTEQ, TomTom',
                        maxZoom: 19,
                        subdomains: '',
                        description: 'Detailní topografická mapa se stínováním brněnských kopců (Kraví hora, Petrov, Špilberk, Černá Pole).',
                        pros: 'Krásné prostorové stínování terénu a výškopisu města.',
                        note: 'Tradiční atlasový vzhled.',
                        codeSnippet: `L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 19,
    attribution: 'Tiles &copy; Esri'
}).addTo(map);`
                    },
                    {
                        id: 'esri-imagery',
                        name: 'ESRI World Imagery',
                        shortName: 'Satelitní',
                        provider: 'Esri / Maxar, Earthstar',
                        recommended: 'Letecké snímky',
                        url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
                        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, Maxar, Earthstar Geographics',
                        maxZoom: 18,
                        subdomains: '',
                        description: 'Fotorealistické satelitní a letecké snímky Brna ve vysokém rozlišení.',
                        pros: 'Reálný pohled na areály univerzit a budovy z ptačí perspektivy.',
                        note: 'Vyžaduje bílé nebo zářivé ohraničení pinů pro čitelnost.',
                        codeSnippet: `L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
    maxZoom: 18,
    attribution: 'Tiles &copy; Esri'
}).addTo(map);`
                    }
                ],

                get currentStyle() {
                    return this.styles.find(s => s.id === this.activeStyleId) || this.styles[0];
                },

                sampleMarkers: [
                    { title: "Fléda Club", category: "Párty & Kluby", lat: 49.2096, lng: 16.6033, color: "#ec4899", desc: "Legendární klub na Štefánikově" },
                    { title: "ArtBar Druhý Pád", category: "Párty & Kluby", lat: 49.2062, lng: 16.6042, color: "#ec4899", desc: "Underground klub a divadlo" },
                    { title: "Koma Lounge & Bar", category: "Párty & Kluby", lat: 49.2040, lng: 16.5985, color: "#ec4899", desc: "Studentský pub quiz & vodní dýmky" },
                    { title: "Filozofická fakulta MUNI", category: "Přednášky", lat: 2001 ? 49.2001 : 49.2001, lng: 16.5986, color: "#3b82f6", desc: "Areál Arna Nováka" },
                    { title: "Fakulta sociálních studií MUNI", category: "Přednášky", lat: 49.1983, lng: 16.6053, color: "#3b82f6", desc: "Joštova 10" },
                    { title: "Přírodovědecká fakulta MUNI", category: "Univerzita", lat: 49.2048, lng: 16.5991, color: "#3b82f6", desc: "Botanická zahrada Kotlářská" },
                    { title: "Koleje Vinařská – Menza", category: "Koleje", lat: 49.1931, lng: 16.5815, color: "#10b981", desc: "Srdce studentského života MUNI" },
                    { title: "Areál MENDELU", category: "Univerzita", lat: 49.2104, lng: 16.6166, color: "#10b981", desc: "Zemědělská 1, Černá Pole" },
                    { title: "Park Lužánky", category: "Outdoor", lat: 49.2065, lng: 16.6110, color: "#f59e0b", desc: "Pikniky a open-air turnaje" },
                    { title: "Univerzitní kino Scala", category: "Kultura", lat: 49.1983, lng: 16.6074, color: "#8b5cf6", desc: "Moravské náměstí" },
                ],

                init() {
                    this.$nextTick(() => {
                        this.initMainMap();
                        this.initMiniMaps();
                    });
                },

                createCustomIcon(color) {
                    return L.divIcon({
                        className: 'custom-preview-marker',
                        html: `
                            <div style="background-color: ${color};" class="w-8 h-8 rounded-full border-2 border-white shadow-md flex items-center justify-center transform -translate-x-1/2 -translate-y-1/2 hover:scale-125 transition-transform duration-150">
                                <svg style="width: 16px; height: 16px; color: white;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        `,
                        iconSize: [32, 32],
                        iconAnchor: [16, 32],
                        popupAnchor: [0, -32]
                    });
                },

                initMainMap() {
                    const container = document.getElementById('main-preview-map');
                    if (!container) return;

                    this.mainMap = L.map('main-preview-map', {
                        scrollWheelZoom: true,
                        touchZoom: true
                    }).setView([49.2025, 16.6045], 14);

                    this.applyTileLayerToMap(this.mainMap, this.currentStyle);

                    // Add markers
                    this.sampleMarkers.forEach(m => {
                        const icon = this.createCustomIcon(m.color);
                        const marker = L.marker([m.lat, m.lng], { icon: icon }).addTo(this.mainMap);
                        marker.bindPopup(`
                            <div style="font-family: inherit; font-size: 13px;" class="p-1">
                                <strong style="display: block; font-size: 14px; margin-bottom: 2px;">${m.title}</strong>
                                <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; background: ${m.color}20; color: ${m.color}; margin-bottom: 4px;">${m.category}</span>
                                <div style="color: #64748b; font-size: 11px;">${m.desc}</div>
                            </div>
                        `);
                    });
                },

                initMiniMaps() {
                    // Initialize mini static previews
                    setTimeout(() => {
                        this.styles.forEach(style => {
                            const elId = 'mini-map-' + style.id;
                            const el = document.getElementById(elId);
                            if (el && !this.miniMaps[style.id]) {
                                const mini = L.map(elId, {
                                    attributionControl: false,
                                    zoomControl: false,
                                    dragging: false,
                                    touchZoom: false,
                                    scrollWheelZoom: false,
                                    doubleClickZoom: false
                                }).setView([49.1975, 16.6070], 14);

                                this.applyTileLayerToMap(mini, style);

                                // Add a single pink pin in the center
                                const icon = this.createCustomIcon('#ec4899');
                                L.marker([49.1983, 16.6074], { icon: icon }).addTo(mini);

                                this.miniMaps[style.id] = mini;
                            }
                        });
                    }, 300);
                },

                applyTileLayerToMap(mapInstance, styleObj) {
                    if (!mapInstance) return;

                    // Remove existing tile layer
                    mapInstance.eachLayer(layer => {
                        if (layer instanceof L.TileLayer) {
                            mapInstance.removeLayer(layer);
                        }
                    });

                    const opts = {
                        maxZoom: styleObj.maxZoom,
                        attribution: styleObj.attribution
                    };
                    if (styleObj.subdomains) {
                        opts.subdomains = styleObj.subdomains;
                    }

                    L.tileLayer(styleObj.url, opts).addTo(mapInstance);
                },

                selectStyle(styleId) {
                    this.activeStyleId = styleId;
                    this.applyTileLayerToMap(this.mainMap, this.currentStyle);
                },

                toggleTheme() {
                    const current = document.documentElement.getAttribute('data-theme') || 'events-light';
                    const next = current === 'events-dark' ? 'events-light' : 'events-dark';
                    document.documentElement.setAttribute('data-theme', next);
                    localStorage.setItem('brno_events_theme', next);

                    if (next === 'events-dark') {
                        document.documentElement.classList.add('dark');
                        // Auto-switch preview to Carto Dark if currently on Positron
                        if (this.activeStyleId === 'carto-positron') {
                            this.selectStyle('carto-dark');
                        }
                    } else {
                        document.documentElement.classList.remove('dark');
                        if (this.activeStyleId === 'carto-dark') {
                            this.selectStyle('carto-positron');
                        }
                    }
                },

                copySnippet() {
                    navigator.clipboard.writeText(this.currentStyle.codeSnippet).then(() => {
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2500);
                    });
                }
            };
        }
    </script>

    {{-- Vlastní styl pro markery --}}
    <style>
        .custom-preview-marker {
            background: transparent;
            border: none;
        }
    </style>
</x-layouts.app>
