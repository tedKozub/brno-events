<!DOCTYPE html>
<html lang="cs" data-theme="events-light" class="transition-colors">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Studentské Akce Brno | Průvodce brněnským studentským životem' }}</title>
    <meta name="description" content="Centralizovaná nástěnka pro studentské večírky, beerpongové turnaje, kvízy a přednášky spolků v Brně (MUNI, VUT, MENDELU).">

    <!-- Okamžitá aplikace uloženého tématu bez probliknutí a podpora Livewire wire:navigate -->
    <script>
        (function() {
            window.applyAppTheme = function() {
                try {
                    var preference = localStorage.getItem('brno_events_theme') || 'system';
                    var isDark = false;
                    if (preference === 'events-dark' || preference === 'dark') {
                        isDark = true;
                    } else if (preference === 'events-light' || preference === 'light') {
                        isDark = false;
                    } else {
                        // system preference
                        isDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                    }

                    var theme = isDark ? 'events-dark' : 'events-light';
                    document.documentElement.setAttribute('data-theme', theme);
                    if (isDark) {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                } catch (e) {}
            };

            // 1. Aplikovat ihned před vykreslením DOM
            window.applyAppTheme();

            // 2. Aplikovat při každé změně stránky přes Livewire wire:navigate (zabrání resetu)
            document.addEventListener('livewire:navigated', function() {
                window.applyAppTheme();
            });

            // 3. Naslouchat systémové změně OS tématu, pokud je aktivní režim 'system'
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function() {
                    var pref = localStorage.getItem('brno_events_theme') || 'system';
                    if (pref === 'system') {
                        window.applyAppTheme();
                    }
                });
            }
        })();
    </script>

    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Leaflet Map CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-base-200 text-base-content min-h-screen font-sans antialiased selection:bg-primary selection:text-primary-content transition-colors">
    {{ $slot }}

    @livewireScripts
</body>
</html>
