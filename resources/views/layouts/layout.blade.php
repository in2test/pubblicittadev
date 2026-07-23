<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $siteName = config('app.name', 'Pubblicittà24');
        $rawTitle = !empty($title) ? trim((string) $title) : null;
        $rawDescription = !empty($description) ? trim((string) $description) : null;
        $rawOgTitle = !empty($ogTitle) ? trim((string) $ogTitle) : null;
        $rawOgDescription = !empty($ogDescription) ? trim((string) $ogDescription) : null;
        $rawOgImage = !empty($ogImage) ? trim((string) $ogImage) : null;
        $rawOgType = !empty($ogType) ? trim((string) $ogType) : 'website';
        $rawCanonical = !empty($canonical) ? trim((string) $canonical) : request()->url();

        $metaTitle = $rawOgTitle ?? ($rawTitle ? $rawTitle . ' | ' . $siteName : $siteName);
        $metaDescription = $rawOgDescription ?? ($rawDescription ?? 'Abbigliamento Personalizzato: stampa e ricamo su t-shirt, polo, felpe e abiti da lavoro. Richiedi un preventivo gratuito online con supporto clienti dedicato.');

        $metaImage = $rawOgImage ?: url('/apple-touch-icon.png');
        if ($metaImage && !\Illuminate\Support\Str::startsWith($metaImage, ['http://', 'https://'])) {
            $metaImage = url($metaImage);
        }
    @endphp

    <title>{{ $rawTitle ? $rawTitle . ' | ' . $siteName : $siteName }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ !empty($robots) ? trim((string) $robots) : 'index, follow' }}">
    <link rel="canonical" href="{{ $rawCanonical }}">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="{{ $rawOgType }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:url" content="{{ $rawCanonical }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    @if(!empty($metaImage))
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:secure_url" content="{{ $metaImage }}">
    @endif

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ $rawCanonical }}">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @if(!empty($metaImage))
    <meta name="twitter:image" content="{{ $metaImage }}">
    @endif

    <!-- Google tag (gtag.js) -->
    @production
    @if (!auth()->check() || !auth()->user()->isAdmin())
    <script>
        function loadGtag() {
            if (window.gtagLoaded) return;
            window.gtagLoaded = true;

            var script = document.createElement('script');
            script.async = true;
            script.src = "https://www.googletagmanager.com/gtag/js?id=G-YGRZSCYNNX";
            document.head.appendChild(script);

            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            window.gtag = gtag;
            
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
        }

        // Delay GTM loading until first user interaction or after 4 seconds of idle time
        var gtmTimer = setTimeout(loadGtag, 4000);

        function triggerGtmLoad() {
            clearTimeout(gtmTimer);
            loadGtag();
            ['mousemove', 'scroll', 'keydown', 'touchstart'].forEach(function(e) {
                window.removeEventListener(e, triggerGtmLoad);
            });
        }

        ['mousemove', 'scroll', 'keydown', 'touchstart'].forEach(function(e) {
            window.addEventListener(e, triggerGtmLoad, { passive: true });
        });
    </script>
    @endif
    @endproduction
    <!-- Preconnect to critical origins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://images.nwgmedia.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
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