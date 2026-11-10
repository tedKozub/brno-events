<div
    class="min-h-screen flex flex-col bg-base-200"
    x-data="{
        copied: false,
        async shareEvent() {
            if (navigator.share) {
                try {
                    await navigator.share({
                        title: @js($event->title),
                        text: @js(Str::limit($event->short_description ?: $event->description, 100)),
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
    {{-- KOMPAKTNÍ NAVBAR – 100% KOHERENTNÍ S HLAVNÍ STRÁNKOU --}}
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur-md sticky top-0 z-30 transition-colors shadow-xs">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-3">
            {{-- Oficiální Brand Logo --}}
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
                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider text-primary bg-primary/10 border border-primary/20">
                        Student
                    </span>
                </div>
            </a>

            {{-- Pravá část: Zpět na akce + Identický Segmented Theme Switcher --}}
            <div class="flex items-center gap-2 sm:gap-3">
                <a
                    href="/"
                    wire:navigate
                    class="btn btn-ghost border border-base-300 font-bold px-4 py-2 min-h-[42px] rounded-xl gap-2 text-sm text-base-content hover:bg-base-200 transition-all inline-flex items-center"
                >
                    <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden xs:inline sm:inline">Zpět na akce</span>
                </a>

                {{-- Přepínač témat (Alpine) – přesná replika z hlavní stránky --}}
                <div
                    x-data="{
                        theme: localStorage.getItem('brno_events_theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'events-dark' : 'events-light'),
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
        </div>
    </header>

    {{-- TOAST NOTIFIKACE PRO KOPÍROVÁNÍ --}}
    <div
        x-show="copied"
        x-transition.opacity
        class="fixed bottom-6 right-6 z-50 bg-neutral text-neutral-content px-4 py-2.5 rounded-xl shadow-xl text-xs font-bold flex items-center gap-2"
        style="display: none;"
    >
        <svg class="w-4 h-4 text-success shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
        </svg>
        <span>Odkaz na akci byl zkopírován!</span>
    </div>

    {{-- HLAVNÍ OBSAH DETAILU --}}
    <main class="max-w-5xl mx-auto px-3 sm:px-6 lg:px-8 py-4 sm:py-8 space-y-6 flex-1 w-full">

        {{-- DROBEČKOVÁ NAVIGACE --}}
        <div class="flex items-center justify-between gap-3 text-xs">
            <a
                href="/"
                wire:navigate
                class="inline-flex items-center gap-1.5 font-bold text-base-content/70 hover:text-primary transition-colors group"
            >
                <svg class="w-4 h-4 text-primary group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                <span>Zpět na přehled všech akcí</span>
            </a>

            <div class="flex items-center gap-2">
                @if($event->category)
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-base-100 text-base-content border border-base-300">
                        {{ $event->category->name }}
                    </span>
                @endif
                @if($event->is_featured)
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-accent text-accent-content">
                        Tip
                    </span>
                @endif
            </div>
        </div>

        {{-- HERO KARTA S PLAKÁTEM A INFORMACEMI --}}
        <div class="card bg-base-100 shadow-sm border border-base-300 rounded-2xl overflow-hidden">
            {{-- Plakát / Fotka akce s poměrem stran --}}
            <div class="relative aspect-[16/9] sm:aspect-[21/9] w-full bg-base-300 overflow-hidden">
                <img
                    src="{{ $event->cover_image ?: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=1200&q=80' }}"
                    alt="{{ $event->title }}"
                    class="w-full h-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent"></div>

                {{-- Horní odznaky --}}
                <div class="absolute top-3 sm:top-4 left-3 sm:left-4 right-3 sm:right-4 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        @if($event->organization)
                            @php
                                $uBadge = match($event->organization->university) {
                                    'MUNI' => 'bg-info/20 text-info border border-info/30',
                                    'VUT' => 'bg-error/20 text-error border border-error/30',
                                    'MENDELU' => 'bg-success/20 text-success border border-success/30',
                                    'UNOB' => 'bg-amber-500/20 text-amber-500 border border-amber-500/30',
                                    'VETUNI' => 'bg-purple-500/20 text-purple-400 border border-purple-500/30',
                                    default => 'bg-neutral/30 text-white border border-white/20',
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider backdrop-blur-md {{ $uBadge }} shadow-sm">
                                {{ $event->organization->university }}
                            </span>
                        @endif
                    </div>

                    {{-- Vstupné badge --}}
                    <div>
                        <span class="px-3 py-1 rounded-lg text-xs font-bold shadow-sm whitespace-nowrap {{ $event->price_type === 'free' ? 'bg-success text-success-content' : 'bg-primary text-primary-content' }}">
                            {{ $event->formatted_price }}
                        </span>
                    </div>
                </div>

                {{-- Spodní info na obrázku --}}
                <div class="absolute bottom-3 sm:bottom-4 left-3 sm:left-4 right-3 sm:right-4 text-white space-y-2">
                    <div class="inline-flex items-center gap-1.5 bg-base-100/95 backdrop-blur-md px-3 py-1 rounded-lg text-xs font-bold text-base-content border border-base-300 shadow-sm whitespace-nowrap">
                        <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $event->starts_at->translatedFormat('l, d. F Y • H:i') }}</span>
                    </div>
                    <h1 class="text-xl sm:text-3xl lg:text-4xl font-black text-white leading-tight drop-shadow-md">
                        {{ $event->title }}
                    </h1>
                </div>
            </div>

            {{-- TĚLO KARTY --}}
            <div class="p-4 sm:p-6 lg:p-8 space-y-6">

                {{-- ORGANIZÁTOR PANEL (BEZ CIRCLE ICONS) --}}
                @if($event->organization)
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 bg-base-200/70 rounded-2xl border border-base-300">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-12 h-12 rounded-xl bg-base-300 overflow-hidden flex items-center justify-center shrink-0 border border-base-300 font-black text-sm text-base-content/70">
                                @if($event->organization->logo_url)
                                    <img src="{{ $event->organization->logo_url }}" alt="{{ $event->organization->name }}" class="w-full h-full object-cover">
                                @else
                                    <span>{{ substr($event->organization->name, 0, 2) }}</span>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-[11px] font-bold uppercase tracking-wider text-base-content/50">Pořádající spolek / Organizace</div>
                                <div class="font-extrabold text-sm sm:text-base text-base-content truncate">{{ $event->organization->name }}</div>
                                @if($event->organization->faculty)
                                    <div class="text-xs text-base-content/70 truncate">{{ $event->organization->faculty }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            @if($event->organization->website_url)
                                <a
                                    href="{{ $event->organization->website_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-ghost border border-base-300 font-bold rounded-xl px-4 py-2 min-h-[42px] text-sm gap-2 inline-flex items-center hover:bg-base-200"
                                >
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                    <span>Web spolku</span>
                                </a>
                            @endif

                            @if($event->organization->instagram_handle)
                                <a
                                    href="https://instagram.com/{{ ltrim($event->organization->instagram_handle, '@') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-ghost border border-base-300 font-bold rounded-xl px-4 py-2 min-h-[42px] text-sm gap-2 inline-flex items-center hover:bg-base-200"
                                >
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                    <span>Instagram</span>
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
                            <div class="font-extrabold text-base-content">{{ $event->starts_at->translatedFormat('l, d. m. Y') }}</div>
                            <div class="text-xs text-base-content/70 mt-0.5">
                                {{ $event->starts_at->format('H:i') }}
                                @if($event->ends_at)
                                    – {{ $event->ends_at->format('H:i') }}
                                @endif
                                <span class="text-base-content/40">({{ $event->starts_at->diffForHumans() }})</span>
                            </div>
                        </div>
                    </div>

                    {{-- Místo konání --}}
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 rounded-xl bg-primary/10 text-primary border border-primary/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-[11px] font-bold text-base-content/50 uppercase tracking-wider">Místo konání</div>
                            <div class="font-extrabold text-base-content">{{ $event->location_display }}</div>
                            @if($event->venue && $event->venue->address)
                                <div class="text-xs text-base-content/70 mt-0.5">{{ $event->venue->address }}, {{ $event->venue->city }}</div>
                            @endif
                            <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                <a
                                    href="https://www.google.com/maps/search/?api=1&query={{ urlencode($event->location_display . ' ' . ($event->venue->address ?? '') . ' Brno') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-ghost border border-base-300 font-bold rounded-xl px-4 py-2 min-h-[42px] text-sm gap-2 inline-flex items-center text-base-content/80 hover:text-primary hover:bg-base-200"
                                >
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    <span>Google Mapy</span>
                                </a>
                                <a
                                    href="https://mapy.cz/zakladni?q={{ urlencode($event->location_display . ' ' . ($event->venue->address ?? '') . ' Brno') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-ghost border border-base-300 font-bold rounded-xl px-4 py-2 min-h-[42px] text-sm gap-2 inline-flex items-center text-base-content/80 hover:text-primary hover:bg-base-200"
                                >
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    <span>Mapy.cz</span>
                                </a>
                            </div>
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
                            class="w-full h-56 sm:h-72 rounded-2xl border border-base-300 overflow-hidden shadow-xs"
                            data-lat="{{ $event->venue->latitude }}"
                            data-lng="{{ $event->venue->longitude }}"
                            data-title="{{ $event->venue->name }}"
                        ></div>
                    </div>
                @endif

                {{-- AKČNÍ LIŠTA – TLAČÍTKA S PERFEKTNÍM PADDINGEM A VÝŠKOU --}}
                <div class="pt-4 border-t border-base-200 flex flex-wrap items-center gap-3">
                    <button
                        wire:click="downloadIcal"
                        type="button"
                        class="btn btn-primary font-bold rounded-xl px-5 py-2.5 min-h-[44px] text-sm gap-2 inline-flex items-center shadow-sm flex-1 sm:flex-none cursor-pointer"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Uložit do kalendáře (.ics)</span>
                    </button>

                    @if($event->ticket_url)
                        <a
                            href="{{ $event->ticket_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-outline border-base-300 font-bold rounded-xl px-5 py-2.5 min-h-[44px] text-sm gap-2 inline-flex items-center flex-1 sm:flex-none"
                        >
                            <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>Koupit vstupenku</span>
                            <svg class="w-3.5 h-3.5 text-base-content/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @elseif($event->facebook_event_url)
                        <a
                            href="{{ $event->facebook_event_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-outline border-base-300 font-bold rounded-xl px-5 py-2.5 min-h-[44px] text-sm gap-2 inline-flex items-center flex-1 sm:flex-none"
                        >
                            <svg class="w-4 h-4 text-primary shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>Facebook událost</span>
                            <svg class="w-3.5 h-3.5 text-base-content/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @elseif($event->source_url && str_starts_with($event->source_url, 'http'))
                        <a
                            href="{{ $event->source_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-outline border-base-300 font-bold rounded-xl px-5 py-2.5 min-h-[44px] text-sm gap-2 inline-flex items-center flex-1 sm:flex-none"
                        >
                            <span>Původní zdroj akce</span>
                            <svg class="w-3.5 h-3.5 text-base-content/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @endif

                    <button
                        type="button"
                        x-on:click="shareEvent"
                        class="btn btn-ghost border border-base-300 font-bold rounded-xl px-5 py-2.5 min-h-[44px] text-sm gap-2 inline-flex items-center flex-1 sm:flex-none"
                    >
                        <svg class="w-4 h-4 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span x-text="copied ? 'Zkopírováno do schránky!' : 'Sdílet akci'">Sdílet akci</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- PODOBNÉ AKCE (DOPORUČENÉ) – PŘESNĚ JAKO NA HLAVNÍ STRÁNCE --}}
        @if($relatedEvents->isNotEmpty())
            <div class="space-y-4 pt-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-black text-base-content">Mohlo by tě také zajímat</h2>
                    <a href="/" wire:navigate class="text-xs font-bold text-primary hover:underline">Zobrazit všechny akce &rarr;</a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 sm:gap-6">
                    @foreach($relatedEvents as $rel)
                        <div class="card bg-base-100 shadow-xs hover:shadow-lg border border-base-300 transition-all duration-200 flex flex-col justify-between overflow-hidden group rounded-2xl">
                            {{-- Horní část: Obrázek & Odznaky --}}
                            <a href="/akce/{{ $rel->slug }}" wire:navigate class="relative aspect-[16/9] w-full bg-base-300 overflow-hidden block">
                                <img
                                    src="{{ $rel->cover_image ?: 'https://images.unsplash.com/photo-1517457373958-b7bdd4587205?auto=format&fit=crop&w=800&q=80' }}"
                                    alt="{{ $rel->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                />

                                {{-- Odznaky přes obrázek --}}
                                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5">
                                    @if($rel->category)
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold backdrop-blur-md bg-neutral/85 text-neutral-content shadow-sm whitespace-nowrap">
                                            {{ $rel->category->name }}
                                        </span>
                                    @endif
                                    @if($rel->is_featured)
                                        <span class="px-3 py-1 rounded-lg text-xs font-bold bg-accent text-accent-content shadow-sm whitespace-nowrap">
                                            Tip
                                        </span>
                                    @endif
                                </div>

                                <div class="absolute top-3 right-3">
                                    <span class="px-3 py-1 rounded-lg text-xs font-bold shadow-sm whitespace-nowrap {{ $rel->price_type === 'free' ? 'bg-success text-success-content' : 'bg-primary text-primary-content' }}">
                                        {{ $rel->formatted_price }}
                                    </span>
                                </div>

                                {{-- Datum v levém dolním rohu --}}
                                <div class="absolute bottom-2.5 left-2.5 bg-base-100/95 backdrop-blur-md px-3 py-1 rounded-lg text-xs font-bold text-base-content border border-base-300 shadow-sm flex items-center gap-1.5 whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>{{ $rel->starts_at->translatedFormat('D d. m. H:i') }}</span>
                                </div>
                            </a>

                            {{-- Tělo karty --}}
                            <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between space-y-3">
                                <div class="space-y-2">
                                    <div class="flex items-center gap-2 text-xs">
                                        @if($rel->organization)
                                            @php
                                                $uBadgeRel = match($rel->organization->university) {
                                                    'MUNI' => 'bg-info/15 text-info border border-info/20',
                                                    'VUT' => 'bg-error/15 text-error border border-error/20',
                                                    'MENDELU' => 'bg-success/15 text-success border border-success/20',
                                                    'UNOB' => 'bg-amber-500/20 text-amber-600 dark:text-amber-400 border border-amber-500/30',
                                                    'VETUNI' => 'bg-purple-500/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
                                                    default => 'bg-neutral/15 text-base-content/70 border border-base-300',
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 rounded text-[11px] font-black uppercase tracking-wider {{ $uBadgeRel }}">
                                                {{ $rel->organization->university }}
                                            </span>
                                            <span class="font-semibold text-base-content/80 truncate">
                                                {{ $rel->organization->name }}
                                            </span>
                                        @else
                                            <span class="font-semibold text-base-content/60">Studentská akce</span>
                                        @endif
                                    </div>

                                    <a
                                        href="/akce/{{ $rel->slug }}"
                                        wire:navigate
                                        class="font-black text-base sm:text-lg leading-snug tracking-tight text-base-content group-hover:text-primary transition-colors cursor-pointer line-clamp-2 block"
                                    >
                                        {{ $rel->title }}
                                    </a>

                                    <div class="flex items-center gap-1.5 text-xs text-base-content/70">
                                        <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <span class="truncate">{{ $rel->location_display }}</span>
                                    </div>
                                </div>

                                <div class="pt-3 border-t border-base-200">
                                    <a
                                        href="/akce/{{ $rel->slug }}"
                                        wire:navigate
                                        class="btn btn-ghost border border-base-300 hover:btn-primary font-bold text-xs w-full rounded-xl gap-1.5 inline-flex items-center justify-center"
                                    >
                                        <span>Detail akce</span>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    {{-- FOOTER SE VSTUPEM PRO SPOLKY – 100% TOTOŽNÝ S HLAVNÍ STRÁNKOU --}}
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
                    class="btn btn-outline border-base-300 font-bold rounded-xl px-4 py-2 min-h-[42px] text-sm gap-2 inline-flex items-center hover:bg-base-200 text-base-content hover:text-primary transition-all"
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

{{-- Leaflet Map Init pro detail --}}
@script
<script>
    let detailMapInstance = null;

    function initDetailMap() {
        const el = document.getElementById('detail-map');
        if (!el || typeof L === 'undefined') return;

        if (detailMapInstance) {
            detailMapInstance.remove();
            detailMapInstance = null;
        }

        const lat = parseFloat(el.getAttribute('data-lat'));
        const lng = parseFloat(el.getAttribute('data-lng'));
        const title = el.getAttribute('data-title') || 'Místo konání';

        if (isNaN(lat) || isNaN(lng)) return;

        detailMapInstance = L.map('detail-map', {
            scrollWheelZoom: false
        }).setView([lat, lng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(detailMapInstance);

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

        L.marker([lat, lng], { icon: pinIcon })
            .addTo(detailMapInstance)
            .bindPopup(`<strong>${title}</strong>`)
            .openPopup();

        setTimeout(() => {
            if (detailMapInstance) {
                detailMapInstance.invalidateSize();
            }
        }, 150);
    }

    document.addEventListener('DOMContentLoaded', initDetailMap);
    document.addEventListener('livewire:navigated', () => setTimeout(initDetailMap, 50));
    initDetailMap();
</script>
@endscript
