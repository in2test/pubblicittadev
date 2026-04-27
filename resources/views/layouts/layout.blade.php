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
</head>







<body class="bg-gray-50 text-gray-900 font-body antialiased relative">
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
