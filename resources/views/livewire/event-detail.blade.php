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
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 py-2.5 flex items-center justify-between gap-2 sm:gap-3">
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
                    class="px-3 sm:px-3.5 py-1.5 rounded-xl font-bold text-xs sm:text-sm inline-flex items-center gap-1.5 transition-all bg-primary text-primary-content shadow-xs"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Akce</span>
                </a>
                <a
                    href="/mista"
                    wire:navigate
                    class="px-3 sm:px-3.5 py-1.5 rounded-xl font-bold text-xs sm:text-sm inline-flex items-center gap-1.5 transition-all text-base-content/70 hover:text-base-content hover:bg-base-200"
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
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
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
        <div class="flex items-center justify-between gap-2 sm:gap-3 text-xs">
            <a
                href="/"
                wire:navigate.hover
                @click.prevent="if (window.history.length > 1) { window.history.back(); } else { Livewire.navigate('/'); }"
                class="inline-flex items-center gap-1.5 font-bold text-base-content/70 hover:text-primary transition-colors group shrink-0 cursor-pointer"
            >
                <svg class="w-4 h-4 text-primary group-hover:-translate-x-0.5 transition-transform shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                <span class="hidden sm:inline">Zpět na přehled všech akcí</span>
                <span class="sm:hidden">Zpět na přehled</span>
            </a>

            <div class="flex items-center gap-2 shrink-0">
                @if($event->category)
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-base-100 text-base-content border border-base-300 truncate max-w-[130px] sm:max-w-none">
                        {{ $event->category->name }}
                    </span>
                @endif
                @if($event->is_featured)
                    <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-accent text-accent-content shrink-0">
                        Tip
                    </span>
                @endif
            </div>
        </div>

        {{-- HERO KARTA S PLAKÁTEM A INFORMACEMI --}}
        <div class="card bg-base-100 shadow-sm border border-base-300 rounded-2xl overflow-hidden">
            {{-- Plakát / Fotka akce s poměrem stran - kliknutím se otevře lightbox --}}
            <div
                class="relative aspect-[16/9] sm:aspect-[21/9] w-full bg-base-300 overflow-hidden cursor-pointer group"
                onclick="document.getElementById('poster_modal').showModal()"
                title="Kliknutím zobrazit v plné velikosti"
            >
                <img
                    src="{{ $event->cover_image_url }}"
                    alt="{{ $event->title }}"
                    class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
                    onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=1200&q=80';"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/35 to-transparent"></div>

                {{-- Horní odznaky --}}
                <div class="absolute top-3 sm:top-4 left-3 sm:left-4 right-3 sm:right-4 flex items-center justify-between gap-2 z-10" onclick="event.stopPropagation()">
                    <div class="flex items-center gap-2 flex-wrap">
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

                        {{-- Tlačítko pro zobrazení plakátu akce --}}
                        @if(!$event->isStockImage())
                            <button
                                type="button"
                                onclick="document.getElementById('poster_modal').showModal()"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-black backdrop-blur-md bg-primary text-primary-content hover:bg-primary/90 border border-primary/30 shadow-sm cursor-pointer transition-transform active:scale-95"
                                title="Zobrazit celý původní plakát akce"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                                </svg>
                                <span>Plakát akce</span>
                            </button>
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
                                <div class="text-[11px] font-bold uppercase tracking-wider text-base-content/50">Pořadatel / Organizace</div>
                                <div class="font-extrabold text-sm sm:text-base text-base-content truncate">{{ $event->organization->name }}</div>
                                @if($event->organization->faculty)
                                    <div class="text-xs text-base-content/70 truncate">{{ $event->organization->faculty }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 shrink-0">
                            @if($event->organization->website_url)
                                <a
                                    href="{{ $event->organization->website_url }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 font-bold rounded-xl px-2.5 sm:px-3 h-8 min-h-[32px] text-xs gap-1.5 inline-flex items-center hover:bg-base-200"
                                >
                                    <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                    <span>Web</span>
                                </a>
                            @endif

                            @if($event->organization->instagram_handle)
                                <a
                                    href="https://instagram.com/{{ ltrim($event->organization->instagram_handle, '@') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="btn btn-xs sm:btn-sm btn-ghost border border-base-300 font-bold rounded-xl px-2.5 sm:px-3 h-8 min-h-[32px] text-xs gap-1.5 inline-flex items-center hover:bg-base-200"
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
                            <div class="font-extrabold text-base-content truncate">
                                {{ $event->venue ? $event->venue->name : ($event->location_name ?: 'Brno') }}
                            </div>
                            @if($event->venue && $event->venue->address)
                                <div class="text-xs text-base-content/70 mt-0.5 truncate">{{ $event->venue->address }}, {{ $event->venue->city }}</div>
                            @elseif($event->location_name && $event->location_name !== ($event->venue?->name))
                                <div class="text-xs text-base-content/70 mt-0.5 truncate">{{ $event->location_name }}</div>
                            @endif
                            <div class="mt-2 flex items-center gap-3">
                                <a
                                    href="https://www.google.com/maps/search/?api=1&query={{ urlencode(($event->venue ? $event->venue->name . ' ' : '') . $event->location_display . ' Brno') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    wire:click="trackOutbound('map')"
                                    class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1"
                                >
                                    <span>Google Mapy</span>
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                </a>
                                <span class="text-base-content/30">•</span>
                                <a
                                    href="https://mapy.cz/zakladni?q={{ urlencode(($event->venue ? $event->venue->name . ' ' : '') . $event->location_display . ' Brno') }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    wire:click="trackOutbound('map')"
                                    class="text-xs font-bold text-primary hover:underline inline-flex items-center gap-1"
                                >
                                    <span>Mapy.cz</span>
                                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
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

                {{-- MAPA DETAILU (ZOBRAZÍ SE PRO KAŽDOU AKCI S ADRESOU / AREÁLEM) --}}
                @php
                    $mapCoords = $event->map_coordinates;
                @endphp
                @if($mapCoords)
                    <div class="space-y-3 pt-2">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-black text-base-content">Kde to najdeš</h2>
                            <span class="text-xs text-base-content/60 font-medium">{{ $mapCoords['title'] }}</span>
                        </div>
                        <div
                            x-data="eventDetailMap(@js($mapCoords))"
                            class="w-full h-56 sm:h-72 rounded-2xl border border-base-300 overflow-hidden shadow-xs relative z-10"
                        >
                            <div x-ref="detailMapCanvas" wire:ignore class="w-full h-full"></div>
                        </div>
                    </div>
                @endif

                {{-- AKČNÍ LIŠTA – TLAČÍTKA HORIZONTÁLNĚ VEDLE SEBE, ÚZKÁ, NE PŘES CELÝ SCREEN --}}
                <div class="pt-3 border-t border-base-200 flex flex-wrap items-center gap-2">
                    @if($event->ticket_url)
                        <a
                            href="{{ $event->ticket_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            wire:click="trackOutbound('ticket')"
                            class="btn btn-sm btn-primary font-bold rounded-xl px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center shadow-xs"
                        >
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            <span>Vstupenky</span>
                            <svg class="w-3 h-3 text-primary-content/70 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @elseif($event->facebook_event_url)
                        <a
                            href="{{ $event->facebook_event_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            wire:click="trackOutbound('facebook')"
                            class="btn btn-sm btn-outline border-base-300 font-bold rounded-xl px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center"
                        >
                            <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                            <span>FB událost</span>
                            <svg class="w-3 h-3 text-base-content/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @elseif($event->source_url && str_starts_with($event->source_url, 'http'))
                        <a
                            href="{{ $event->source_url }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            wire:click="trackOutbound('source')"
                            class="btn btn-sm btn-outline border-base-300 font-bold rounded-xl px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center"
                        >
                            <span>Zdroj</span>
                            <svg class="w-3 h-3 text-base-content/50 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>
                    @endif

                    <button
                        wire:click="downloadIcal"
                        type="button"
                        class="btn btn-sm {{ $event->ticket_url ? 'btn-outline border-base-300' : 'btn-primary' }} font-bold rounded-xl px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center shadow-xs cursor-pointer"
                        title="Stáhnout do kalendáře (.ics)"
                    >
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Kalendář</span>
                    </button>

                    <button
                        type="button"
                        x-on:click="shareEvent(); $wire.trackOutbound('share');"
                        class="btn btn-sm btn-ghost border border-base-300 font-bold rounded-xl px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center"
                        title="Sdílet odkaz na akci"
                    >
                        <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        <span x-text="copied ? 'Zkopírováno!' : 'Sdílet'">Sdílet</span>
                    </button>

                    <button
                        type="button"
                        onclick="document.getElementById('poster_modal').showModal()"
                        class="btn btn-sm btn-ghost border border-base-300 font-bold rounded-xl px-3 h-9 min-h-[36px] text-xs gap-1.5 inline-flex items-center cursor-pointer"
                        title="Zobrazit celý originální plakát akce"
                    >
                        <svg class="w-3.5 h-3.5 text-primary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>Plakát akce</span>
                    </button>

                    <div class="sm:ml-auto">
                        <livewire:report-event-modal :event-id="$event->id" />
                    </div>
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
                                    src="{{ $rel->cover_image_url }}"
                                    alt="{{ $rel->title }}"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=1200&q=80';"
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
                                    @if($rel->effective_cover_image_type === 'poster')
                                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-black tracking-wide backdrop-blur-md bg-primary/90 text-primary-content shadow-sm whitespace-nowrap inline-flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <span>Plakát</span>
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

    {{-- MODERNÍ FULL-SCREEN LIGHTBOX PRO ZOBRAZENÍ PLAKÁTU ČI BANNERU VE VYSOKÉM ROZLIŠENÍ --}}
    <dialog id="poster_modal" class="modal [&:not([open])]:hidden bg-black/85 backdrop-blur-md p-2 sm:p-6 z-50">
        <div class="relative w-full max-w-6xl max-h-[96vh] flex flex-col items-center justify-center p-0 m-auto select-none">
            {{-- Horní ovládací lišta --}}
            <div class="flex items-center justify-between w-full pb-3 px-2 text-white gap-3 shrink-0">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="text-sm sm:text-base font-black truncate drop-shadow">{{ $event->title }}</span>
                    <span class="badge badge-primary badge-sm text-[11px] font-bold shrink-0">{{ $event->cover_image_type_display }}</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    @php
                        $sourceUrl = $event->original_image_url
                            ?: ($event->source_url
                            ?: ($event->instagram_post_url
                            ?: ($event->facebook_event_url
                            ?: ($event->organization?->website_url ?: $event->cover_image_url))));
                    @endphp
                    @if($sourceUrl)
                        <a
                            href="{{ $sourceUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn btn-xs sm:btn-sm btn-ghost text-white/90 hover:text-white hover:bg-white/15 gap-1.5 rounded-xl font-bold"
                            title="Otevřít původní zdroj"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            <span>Původní zdroj</span>
                        </a>
                    @endif
                    <button
                        type="button"
                        onclick="document.getElementById('poster_modal').close()"
                        class="btn btn-sm btn-circle bg-white/20 hover:bg-white/35 text-white border-none shadow-md ml-1 cursor-pointer"
                        aria-label="Zavřít"
                    >✕</button>
                </div>
            </div>

            {{-- Velký obrázek bez stísňujících rámečků --}}
            <div class="w-full flex justify-center items-center overflow-auto max-h-[86vh] sm:max-h-[90vh] py-1">
                <img
                    src="{{ $event->cover_image_url }}"
                    alt="{{ $event->title }}"
                    class="max-w-full max-h-[85vh] sm:max-h-[88vh] object-contain rounded-2xl shadow-2xl"
                    loading="lazy"
                    onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1523580494863-6f3031224c94?auto=format&fit=crop&w=1200&q=80';"
                />
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" onclick="document.getElementById('poster_modal').close()">Zavřít</button>
        </form>
    </dialog>

    {{-- FOOTER SE VSTUPEM PRO SPOLKY A PWA INSTALACÍ --}}
    <x-footer />
</div>

{{-- Leaflet Map Init pro detail --}}
@script
<script>
    window.eventDetailMap = function(coords) {
        return {
            map: null,
            init() {
                this.$nextTick(() => {
                    if (typeof L === 'undefined' || !this.$refs.detailMapCanvas) return;

                    const lat = parseFloat(coords.lat);
                    const lng = parseFloat(coords.lng);
                    const title = coords.title || 'Místo konání';

                    if (isNaN(lat) || isNaN(lng)) return;

                    if (this.map) {
                        try {
                            this.map.remove();
                        } catch (e) {}
                        this.map = null;
                    }

                    this.map = L.map(this.$refs.detailMapCanvas, {
                        scrollWheelZoom: true,
                        touchZoom: true,
                        dragging: true,
                        tap: true
                    }).setView([lat, lng], 15);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors',
                        maxZoom: 19,
                    }).addTo(this.map);

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
                        .addTo(this.map)
                        .bindPopup(`<strong>${title}</strong>`)
                        .openPopup();

                    setTimeout(() => {
                        if (this.map) {
                            this.map.invalidateSize();
                        }
                    }, 200);
                });
            },
            destroy() {
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
