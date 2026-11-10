<div
    class="min-h-screen flex flex-col bg-base-200"
    x-data="{
        theme: localStorage.getItem('brno_events_theme') || 'events-light',
        copied: false,
        toggleTheme() {
            this.theme = this.theme === 'events-light' ? 'events-dark' : 'events-light';
            document.documentElement.setAttribute('data-theme', this.theme);
            if (this.theme === 'events-dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            localStorage.setItem('brno_events_theme', this.theme);
        },
        async shareEvent() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: '{{ addslashes($event->title) }}',
                        text: '{{ addslashes(Str::limit($event->short_description ?: $event->description, 100)) }}',
                        url: window.location.href,
                    });
                } catch (e) {}
            } else {
                await navigator.clipboard.writeText(window.location.href);
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            }
        }
    }"
>
    {{-- TOP STICKY NAVBAR --}}
    <header class="sticky top-0 z-40 bg-base-100/90 backdrop-blur-md border-b border-base-300">
        <div class="max-w-4xl mx-auto px-4 h-14 sm:h-16 flex items-center justify-between gap-3">
            {{-- Zpět tlačítko s nativní historií --}}
            <button
                type="button"
                x-on:click="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '/'; }"
                class="btn btn-sm sm:btn-md btn-ghost gap-2 font-bold text-xs sm:text-sm pl-1 pr-3"
            >
                <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Zpět na akce</span>
            </button>

            {{-- Střed: Brand název --}}
            <a href="/" wire:navigate class="font-black text-sm sm:text-base tracking-tight text-base-content hover:text-primary transition-colors flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-primary inline-block"></span>
                <span>Brno<span class="text-primary font-black">Akce</span></span>
            </a>

            {{-- Pravá strana: Sdílet & Téma --}}
            <div class="flex items-center gap-1.5">
                {{-- Sdílet tlačítko --}}
                <button
                    type="button"
                    x-on:click="shareEvent"
                    class="btn btn-sm btn-ghost btn-circle text-base-content/80 hover:text-primary"
                    title="Sdílet akci"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                </button>

                {{-- Přepínač témat --}}
                <button
                    type="button"
                    x-on:click="toggleTheme"
                    class="btn btn-sm btn-ghost btn-circle"
                    title="Přepnout motiv (světlý / tmavý)"
                >
                    <template x-if="theme === 'events-light'">
                        <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 01-1.5 0H21a.75.75 0 01.75-.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-2.25A.75.75 0 0112 18zM7.758 17.303a.75.75 0 00-1.061-1.06l-1.591 1.59a.75.75 0 001.06 1.061l1.591-1.59zM6 12a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h2.25A.75.75 0 016 12zM6.697 7.757a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 00-1.061 1.06l1.59 1.591z"/>
                        </svg>
                    </template>
                    <template x-if="theme === 'events-dark'">
                        <svg class="w-4 h-4 text-sky-400" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z" clip-rule="evenodd"/>
                        </svg>
                    </template>
                </button>
            </div>
        </div>
    </header>

    {{-- TOAST NOTIFIKACE PRO KOPÍROVÁNÍ --}}
    <div
        x-show="copied"
        x-transition.opacity
        class="fixed bottom-6 right-6 z-50 bg-neutral text-neutral-content px-4 py-2.5 rounded-xl shadow-xl text-xs font-bold flex items-center gap-2"
        style="display: none;"
    >
        <svg class="w-4 h-4 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <span>Odkaz na akci byl zkopírován do schránky!</span>
    </div>

    {{-- HLAVNÍ OBSAH DETAILU --}}
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 py-6 sm:py-8 space-y-6 sm:space-y-8">
        {{-- HERO KARTA S PLAKÁTEM A ODZNAKY --}}
        <div class="card bg-base-100 border border-base-300 shadow-md overflow-hidden">
            {{-- Plakát / Vizuál --}}
            <div class="relative h-56 sm:h-80 md:h-96 w-full bg-neutral overflow-hidden">
                <img
                    src="{{ $event->cover_image_url }}"
                    alt="{{ $event->title }}"
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-base-900/90 via-base-900/40 to-transparent"></div>

                {{-- Plovoucí odznaky v horním rohu --}}
                <div class="absolute top-4 left-4 right-4 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        @if($event->organization)
                            @php
                                $uBadge = match($event->organization->university) {
                                    'MUNI' => 'bg-info text-info-content',
                                    'VUT' => 'bg-error text-error-content',
                                    'MENDELU' => 'bg-success text-success-content',
                                    'UNOB' => 'bg-amber-600 text-white',
                                    'VETUNI' => 'bg-purple-600 text-white',
                                    default => 'bg-neutral text-neutral-content',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider {{ $uBadge }} shadow-md">
                                {{ $event->organization->university }}
                            </span>
                        @endif

                        @if($event->category)
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-base-100/90 text-base-content backdrop-blur-md border border-base-300/50 shadow-md">
                                {{ $event->category->name }}
                            </span>
                        @endif
                    </div>

                    {{-- Vstupné badge --}}
                    <div>
                        <span class="px-3 py-1 rounded-lg text-xs font-black tracking-wide shadow-md {{ $event->price_type === 'free' ? 'bg-success text-success-content' : 'bg-primary text-primary-content' }}">
                            {{ $event->formatted_price }}
                        </span>
                    </div>
                </div>

                {{-- Datum na spodku obrázku pro mobil --}}
                <div class="absolute bottom-4 left-4 right-4 text-white">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-black/60 backdrop-blur-md text-xs font-bold mb-2">
                        <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $event->starts_at->translatedFormat('l, d. F Y • H:i') }}</span>
                    </div>
                    <h1 class="text-2xl sm:text-4xl font-black leading-tight drop-shadow-sm">
                        {{ $event->title }}
                    </h1>
                </div>
            </div>

            {{-- TĚLO KARTY --}}
            <div class="p-5 sm:p-8 space-y-6">
                {{-- ORGANIZÁTOR INFO --}}
                @if($event->organization)
                    <div class="flex items-center justify-between p-4 bg-base-200/70 rounded-2xl border border-base-300">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-base-300 overflow-hidden flex items-center justify-center shrink-0 border border-base-300">
                                @if($event->organization->logo_url)
                                    <img src="{{ $event->organization->logo_url }}" alt="{{ $event->organization->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="font-black text-sm text-base-content/70">{{ substr($event->organization->name, 0, 2) }}</span>
                                @endif
                            </div>
                            <div>
                                <div class="text-[11px] font-bold uppercase tracking-wider text-base-content/50">Pořádající spolek / Organizace</div>
                                <div class="font-extrabold text-sm sm:text-base text-base-content">{{ $event->organization->name }}</div>
                                @if($event->organization->faculty)
                                    <div class="text-xs text-base-content/70">{{ $event->organization->faculty }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($event->organization->website_url)
                                <a
                                    href="{{ $event->organization->website_url }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-sm btn-ghost btn-circle"
                                    title="Web spolku"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                </a>
                            @endif

                            @if($event->organization->instagram_handle)
                                <a
                                    href="https://instagram.com/{{ ltrim($event->organization->instagram_handle, '@') }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-sm btn-ghost btn-circle"
                                    title="Instagram"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                {{-- KLÍČOVÉ INFORMACE GRID --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 p-4 sm:p-5 bg-base-200/50 rounded-2xl border border-base-300 text-xs sm:text-sm">
                    {{-- Datum & Čas --}}
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="text-[11px] font-bold text-base-content/50 uppercase tracking-wider">Datum a čas</div>
                            <div class="font-extrabold text-base-content">{{ $event->starts_at->translatedFormat('d. m. Y') }}</div>
                            <div class="text-xs text-base-content/70">
                                {{ $event->starts_at->format('H:i') }}
                                @if($event->ends_at)
                                    – {{ $event->ends_at->format('H:i') }}
                                @endif
                                ({{ $event->starts_at->diffForHumans() }})
                            </div>
                        </div>
                    </div>

                    {{-- Místo konání --}}
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-secondary/10 text-secondary border border-secondary/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-[11px] font-bold text-base-content/50 uppercase tracking-wider">Místo konání</div>
                            <div class="font-extrabold text-base-content">{{ $event->location_display }}</div>
                            @if($event->venue && $event->venue->address)
                                <div class="text-xs text-base-content/70">{{ $event->venue->address }}, {{ $event->venue->city }}</div>
                                <div class="mt-1 flex items-center gap-2">
                                    <a
                                        href="https://www.google.com/maps/search/?api=1&query={{ urlencode($event->location_display . ' ' . $event->venue->address . ' Brno') }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1"
                                    >
                                        <span>Google Mapy</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                    <span class="text-base-content/30">•</span>
                                    <a
                                        href="https://mapy.cz/zakladni?q={{ urlencode($event->location_display . ' ' . $event->venue->address . ' Brno') }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="text-[11px] font-bold text-primary hover:underline flex items-center gap-1"
                                    >
                                        <span>Mapy.cz</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- POPIS AKCE --}}
                <div class="space-y-3">
                    <h2 class="text-lg font-black text-base-content">O události</h2>
                    <div class="text-sm sm:text-base text-base-content/85 leading-relaxed whitespace-pre-line">
                        {{ $event->description ?: ($event->short_description ?: 'Bližší informace k této akci naleznete na stránkách pořadatelského spolku.') }}
                    </div>
                </div>

                {{-- MAPA DETAILU (POKUD MÁ GPS) --}}
                @if($event->venue && $event->venue->latitude && $event->venue->longitude)
                    <div class="space-y-3 pt-2">
                        <h2 class="text-base font-black text-base-content">Kde to najdeš</h2>
                        <div
                            id="detail-map"
                            class="w-full h-56 sm:h-72 rounded-2xl border border-base-300 overflow-hidden shadow-inner"
                            data-lat="{{ $event->venue->latitude }}"
                            data-lng="{{ $event->venue->longitude }}"
                            data-title="{{ $event->venue->name }}"
                        ></div>
                    </div>
                @endif

                {{-- AKČNÍ LIŠTA: .ICS, VSTUPENKY, SDÍLENÍ --}}
                <div class="pt-4 border-t border-base-200 flex flex-wrap items-center gap-3">
                    <button
                        wire:click="downloadIcal"
                        class="btn btn-primary text-primary-content gap-2 font-bold shadow-md flex-1 sm:flex-none cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Uložit do kalendáře (.ics)</span>
                    </button>

                    @if($event->ticket_url)
                        <a
                            href="{{ $event->ticket_url }}"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-neutral gap-2 font-bold flex-1 sm:flex-none"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>Koupit vstupenku</span>
                        </a>
                    @elseif($event->source_url && str_starts_with($event->source_url, 'http'))
                        <a
                            href="{{ $event->source_url }}"
                            target="_blank"
                            rel="noopener"
                            class="btn btn-neutral btn-outline gap-2 font-bold flex-1 sm:flex-none"
                        >
                            <span>Původní zdroj akce</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @endif

                    <button
                        type="button"
                        x-on:click="shareEvent"
                        class="btn btn-ghost border border-base-300 gap-2 font-bold flex-1 sm:flex-none"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span>Sdílet</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- PODOBNÉ AKCE (DOPORUČENÉ) --}}
        @if($relatedEvents->isNotEmpty())
            <div class="space-y-4 pt-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-base-content">Mohlo by tě také zajímat</h2>
                    <a href="/" wire:navigate class="text-xs font-bold text-primary hover:underline">Zobrazit všechny akce &rarr;</a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach($relatedEvents as $rel)
                        <a
                            href="/akce/{{ $rel->slug }}"
                            wire:navigate
                            class="card bg-base-100 border border-base-300 hover:border-primary/50 transition-all hover:-translate-y-0.5 shadow-sm overflow-hidden flex flex-col group cursor-pointer"
                        >
                            <div class="relative h-28 w-full bg-neutral overflow-hidden">
                                <img
                                    src="{{ $rel->cover_image_url }}"
                                    alt="{{ $rel->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                />
                                <div class="absolute bottom-2 left-2 px-2 py-0.5 rounded bg-black/60 text-white text-[10px] font-bold">
                                    {{ $rel->starts_at->translatedFormat('D d. m. H:i') }}
                                </div>
                            </div>
                            <div class="p-3.5 flex-1 flex flex-col justify-between space-y-2">
                                <div>
                                    <div class="text-[10px] font-black uppercase text-primary">
                                        {{ $rel->organization?->university ?? 'Akce' }}
                                    </div>
                                    <h3 class="font-bold text-xs sm:text-sm text-base-content line-clamp-2 group-hover:text-primary transition-colors">
                                        {{ $rel->title }}
                                    </h3>
                                </div>
                                <div class="text-[11px] font-bold text-base-content/60 flex items-center justify-between">
                                    <span>{{ $rel->location_display }}</span>
                                    <span class="text-primary font-black">{{ $rel->formatted_price }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    {{-- FOOTER --}}
    <footer class="mt-auto border-t border-base-300 bg-base-100/60 py-6 text-center text-xs text-base-content/60 space-y-2">
        <div>
            Studentské Akce Brno &copy; {{ date('Y') }} • Průvodce pro MUNI, VUT, MENDELU, UNOB & VETUNI
        </div>
        <div>
            <a href="/admin" class="font-bold text-primary hover:underline">Vstup pro zástupce spolků</a>
        </div>
    </footer>
</div>

{{-- Leaflet Map Init pro detail --}}
@script
<script>
    document.addEventListener('livewire:navigated', () => {
        initDetailMap();
    });

    function initDetailMap() {
        const el = document.getElementById('detail-map');
        if (!el || typeof L === 'undefined') return;

        const lat = parseFloat(el.getAttribute('data-lat'));
        const lng = parseFloat(el.getAttribute('data-lng'));
        const title = el.getAttribute('data-title');

        if (isNaN(lat) || isNaN(lng)) return;

        const map = L.map(el, {
            scrollWheelZoom: false
        }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        const customIcon = L.divIcon({
            className: 'custom-pin-detail',
            html: `
                <div style="background-color: #ea580c; width: 34px; height: 34px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 4px 10px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; color: white;">
                    <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    </svg>
                </div>
            `,
            iconSize: [34, 34],
            iconAnchor: [17, 34],
            popupAnchor: [0, -34],
        });

        L.marker([lat, lng], { icon: customIcon })
            .addTo(map)
            .bindPopup(`<strong>${title}</strong>`)
            .openPopup();
    }

    initDetailMap();
</script>
@endscript
