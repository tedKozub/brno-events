@props([
    'title' => 'Studentské Akce Brno | Průvodce brněnským studentským životem',
    'description' => 'Centralizovaný portál a kalendář pro studentské párty, festivaly, workshopy, kvízy a spolkové akce v Brně (MUNI, VUT, MENDELU, UNOB, VETUNI).',
    'ogImage' => null,
    'ogType' => 'website',
    'ogUrl' => null,
    'schemaJson' => null,
])
<!DOCTYPE html>
<html lang="cs" data-theme="events-light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Rychlá aplikace uloženého tématu bez probliknutí -->
    <script data-navigate-once>
        window.applyAppTheme = function() {
            try {
                var preference = localStorage.getItem('brno_events_theme') || 'system';
                var isDark = false;
                if (preference === 'events-dark' || preference === 'dark') {
                    isDark = true;
                } else if (preference === 'events-light' || preference === 'light') {
                    isDark = false;
                } else {
                    isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                }

                var theme = isDark ? 'events-dark' : 'events-light';
                document.documentElement.setAttribute('data-theme', theme);
                if (isDark) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                var themeMeta = document.querySelector('meta[name="theme-color"]');
                if (themeMeta) {
                    themeMeta.setAttribute('content', isDark ? '#0f172a' : '#ea580c');
                }
            } catch (e) {}
        };

        window.applyAppTheme();

        document.addEventListener('livewire:navigated', function() {
            window.applyAppTheme();
        });

        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                var pref = localStorage.getItem('brno_events_theme') || 'system';
                if (pref === 'system') {
                    window.applyAppTheme();
                }
            });
        }
    </script>

    <title>{{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $ogUrl ?? url()->current() }}">

    <!-- OpenGraph (Facebook, WhatsApp, LinkedIn, iMessage) -->
    <meta property="og:site_name" content="BrnoAkce">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:url" content="{{ $ogUrl ?? url()->current() }}">
    <meta property="og:image" content="{{ $ogImage ?? asset('images/og-brnoakce.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="cs_CZ">

    <!-- Twitter / X Cards -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">
    <meta name="twitter:image" content="{{ $ogImage ?? asset('images/og-brnoakce.jpg') }}">

    <!-- Favicon & Mobile Icons -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="BrnoAkce">
    <meta name="application-name" content="BrnoAkce">

    <!-- Schema.org JSON-LD Structured Data -->
    @if(!empty($schemaJson))
        <script type="application/ld+json">
            {!! $schemaJson !!}
        </script>
    @else
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "BrnoAkce",
            "url": "{{ config('app.url', 'https://brnoakce.tadeaskozub.com') }}",
            "description": "Průvodce brněnským studentským životem a centrální kalendář studentských akcí v Brně.",
            "inLanguage": "cs"
        }
        </script>
    @endif

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com" data-navigate-once>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin data-navigate-once>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" data-navigate-once>

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" data-navigate-once/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" data-navigate-once></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-base-200 text-base-content min-h-screen font-sans antialiased selection:bg-primary selection:text-primary-content">
    {{ $slot }}

    {{-- PWA INSTALL MODAL (iPhone / iOS & Android) --}}
    <dialog id="pwa_install_modal" class="modal modal-bottom sm:modal-middle" data-navigate-once>
        <div class="modal-box bg-base-100 border border-base-300 shadow-2xl p-5 sm:p-6 max-w-md">
            {{-- Header with App Icon --}}
            <div class="flex items-center gap-3.5 pb-4 border-b border-base-200">
                <img src="/icons/icon-192x192.png" alt="BrnoAkce" class="w-12 h-12 rounded-2xl shadow-md border border-base-300 shrink-0">
                <div>
                    <h3 class="font-black text-base sm:text-lg text-base-content tracking-tight">Nainstalovat BrnoAkce</h3>
                    <p class="text-xs text-base-content/70">Měj přehled studentských akcí v Brně vždy po ruce</p>
                </div>
            </div>

            {{-- iOS / iPhone Instructions --}}
            <div class="py-4 space-y-3.5 text-xs sm:text-sm text-base-content/85">
                <p class="text-xs font-semibold text-base-content/70 uppercase tracking-wider">Jak přidat aplikaci na iPhone / iPad:</p>
                
                <div class="flex items-start gap-3 bg-base-200/60 p-3 rounded-xl border border-base-200">
                    <div class="p-2 bg-base-100 rounded-lg shadow-sm border border-base-300 shrink-0">
                        <svg class="w-5 h-5 text-sky-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                    </div>
                    <div>
                        <span class="font-bold text-base-content block">1. V Safari klikni na Sdílet</span>
                        <span class="text-xs text-base-content/70">Dole na liště Safari (na iPadu nahoře) klepni na ikonu čtverečku se šipkou nahoru.</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-base-200/60 p-3 rounded-xl border border-base-200">
                    <div class="p-2 bg-base-100 rounded-lg shadow-sm border border-base-300 shrink-0">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="3" width="18" height="18" rx="4" stroke-width="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8" />
                        </svg>
                    </div>
                    <div>
                        <span class="font-bold text-base-content block">2. Zvol „Přidat na plochu“</span>
                        <span class="text-xs text-base-content/70">V nabídce sjeď kousek níže a klikni na řádek s ikonou plus.</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 bg-base-200/60 p-3 rounded-xl border border-base-200">
                    <div class="p-2 bg-base-100 rounded-lg shadow-sm border border-base-300 shrink-0">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <span class="font-bold text-base-content block">3. Vpravo nahoře potvrď „Přidat“</span>
                        <span class="text-xs text-base-content/70">Aplikace se ti připne na plochu a poběží na celou obrazovku bez lišt prohlížeče!</span>
                    </div>
                </div>

                <div class="text-[11px] text-base-content/60 pt-1 text-center">
                    <span>Máš Android? V menu Chrome (tři tečky) zvol <b>„Instalovat aplikaci“</b>.</span>
                </div>
            </div>

            {{-- Action Button --}}
            <div class="modal-action mt-2 pt-3 border-t border-base-200 flex items-center justify-end">
                <form method="dialog">
                    <button class="btn btn-primary btn-sm px-6 font-bold rounded-xl">Rozumím</button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>Zavřít</button>
        </form>
    </dialog>

    {{-- COOKIE CONSENT & PRIVACY BANNER --}}
    <x-cookie-consent />

    @livewireScripts

    <!-- PWA Service Worker & Install Handler -->
    <script data-navigate-once>
        window.pwaDeferredPrompt = null;
        window.addEventListener('beforeinstallprompt', function(e) {
            e.preventDefault();
            window.pwaDeferredPrompt = e;
        });

        window.openPwaInstallModal = function() {
            if (window.pwaDeferredPrompt) {
                window.pwaDeferredPrompt.prompt();
                window.pwaDeferredPrompt.userChoice.then(function() {
                    window.pwaDeferredPrompt = null;
                });
                return;
            }

            var modal = document.getElementById('pwa_install_modal');
            if (modal && typeof modal.showModal === 'function') {
                modal.showModal();
            }
        };

        if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').then(function(reg) {
                    console.log('BrnoAkce PWA Service Worker registered:', reg.scope);
                }).catch(function(err) {
                    console.log('BrnoAkce PWA Service Worker registration failed:', err);
                });
            });
        }
    </script>
</body>
</html>
