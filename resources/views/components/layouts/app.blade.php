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

    <!-- PWA & Mobile Icons -->
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
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

    @livewireScripts

    <!-- PWA Service Worker Registration -->
    <script>
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
