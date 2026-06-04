<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? '' }} {{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="description" content="{{ $description ?? '' }}">
    <meta name="robots" content="{{ $robots ?? '' }}">
    <link rel="canonical" href="{{ $canonical ?? '' }}">

    <!-- Google tag (gtag.js) -->
    @production
    @if (!auth()->check() || !auth()->user()->isAdmin())
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-YGRZSCYNNX"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        
        // Default consent to denied
        gtag('consent', 'default', {
            'analytics_storage': 'denied'
        });

        // Update if already accepted
        if (localStorage.getItem('cookie_consent') === 'all') {
            gtag('consent', 'update', {
                'analytics_storage': 'granted'
            });
        }

        gtag('js', new Date());
        gtag('config', 'G-YGRZSCYNNX');
    </script>
    @endif
    @endproduction
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&amp;family=Roboto+Mono:wght@400;500&amp;display=swap">
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&amp;family=Roboto+Mono:wght@400;500&amp;display=swap" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&amp;family=Roboto+Mono:wght@400;500&amp;display=swap">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap">
    </noscript>
    @fluxAppearance
</head>







<body class="bg-gray-50 text-gray-900 font-body antialiased relative text-base">
    <!-- Navigation -->
    <div class=" bg-accent-500 py-2 text-center w-full shadow-md font-mono uppercase tracking-widest text-sm text-gray-950">
        Trasporto gratuito per ordini superiori a €200
    </div>
    <livewire:navigation />
    @if (request()->is('categories'))
    <x-sidebar />
    <!-- Sidebar -->
    @endif
    <!-- Main Content -->
    <main class="pt-4 lg:pt-20">
        {{ $slot }}
    </main>
    <x-footer />
    @livewireScripts
    @fluxScripts
    <script>
        function openAuthModal() {
            if (window.Livewire) {
                Livewire.dispatch('open-auth-modal');
            } else {
                window.dispatchEvent(new CustomEvent('open-auth-modal'));
            }
        }
    </script>
    <livewire:auth-modal />
    <x-cookie-banner />
</body>

</html>