<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ __('Welcome') }} - {{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Google tag (gtag.js) -->
    @production
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-YGRZSCYNNX"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());

            gtag('config', 'G-YGRZSCYNNX');
        </script>
    @endproduction
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800;900&amp;family=Roboto+Mono:wght@400;500&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "surface-bright": "#f9f9fc",
                        "on-secondary-fixed-variant": "#454749",
                        "inverse-on-surface": "#f0f0f3",
                        "surface": "#f9f9fc",
                        "surface-container-low": "#f3f3f6",
                        "secondary": "#5d5e61",
                        "secondary-container": "#e2e2e5",
                        "error": "#ba1a1a",
                        "surface-container-highest": "#e2e2e5",
                        "primary": "#750005",
                        "outline-variant": "#e3beb9",
                        "secondary-fixed": "#e2e2e5",
                        "surface-container": "#eeeef0",
                        "on-secondary-container": "#636467",
                        "surface-variant": "#e2e2e5",
                        "inverse-surface": "#2f3133",
                        "surface-container-high": "#e8e8ea",
                        "outline": "#8f706c",
                        "on-tertiary-fixed-variant": "#004494",
                        "secondary-fixed-dim": "#c6c6c9",
                        "surface-dim": "#dadadc",
                        "surface-tint": "#b7221e",
                        "tertiary-fixed": "#d8e2ff",
                        "on-primary-fixed": "#410002",
                        "inverse-primary": "#ffb4aa",
                        "on-secondary-fixed": "#1a1c1e",
                        "on-tertiary-container": "#a1beff",
                        "error-container": "#ffdad6",
                        "background": "#f9f9fc",
                        "tertiary": "#003475",
                        "on-tertiary": "#ffffff",
                        "primary-fixed": "#ffdad5",
                        "on-surface-variant": "#5b403d",
                        "on-error": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "on-secondary": "#ffffff",
                        "on-error-container": "#93000a",
                        "tertiary-fixed-dim": "#adc6ff",
                        "on-surface": "#1a1c1e",
                        "tertiary-container": "#004aa1",
                        "on-tertiary-fixed": "#001a41",
                        "primary-container": "#9e0b0f",
                        "on-primary-container": "#ffa99f",
                        "on-primary": "#ffffff",
                        "primary-fixed-dim": "#ffb4aa",
                        "on-primary-fixed-variant": "#930009",
                        "on-background": "#1a1c1e"
                    },
                    fontFamily: {
                        "headline": ["Inter", "sans-serif"],
                        "body": ["Inter", "sans-serif"],
                        "label": ["Inter", "sans-serif"],
                        "mono": ["Roboto Mono", "monospace"]
                    },

                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .text-vertical {
            writing-mode: vertical-rl;
        }

        .bg-grid-subtle {
            background-image: radial-gradient(circle, #e2e2e5 1px, transparent 1px);
            background-size: 32px 32px;
        }
    </style>


</head>







<body class="bg-surface text-on-surface font-body antialiased">
    <!-- Navigation -->
    <x-navigation />
    @if (request()->is('categories'))
        <x-sidebar />
        <!-- Sidebar -->
    @endif
    <!-- Main Content -->
    <main class="pt-20">
        {{ $slot }}

    </main>
    <x-footer />
    @livewireScripts
</body>

</html>
