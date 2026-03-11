<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ $siteSettings['general']['site_name'] ?? 'SMAN 1 Baleendah' }}</title>

        <!-- DNS Prefetch & Preconnect for Performance -->
        <link rel="dns-prefetch" href="https://fonts.googleapis.com">
        <link rel="dns-prefetch" href="https://fonts.gstatic.com">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <!-- Fonts with font-display: swap for performance -->
        <link href="https://fonts.googleapis.com/css2?family=Merriweather:ital,wght@0,300;0,400;0,700;0,900;1,300;1,400;1,700;1,900&family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

        <link rel="icon" href="{{ (isset($siteSettings['general']['site_favicon']) && !str_starts_with($siteSettings['general']['site_favicon'], '/')) ? asset('storage/' . $siteSettings['general']['site_favicon']) : asset($siteSettings['general']['site_favicon'] ?? 'favicon.ico') }}" type="image/x-icon">
        <link rel="shortcut icon" href="{{ (isset($siteSettings['general']['site_favicon']) && !str_starts_with($siteSettings['general']['site_favicon'], '/')) ? asset('storage/' . $siteSettings['general']['site_favicon']) : asset($siteSettings['general']['site_favicon'] ?? 'favicon.ico') }}" type="image/x-icon">
        <link rel="apple-touch-icon" href="{{ (isset($siteSettings['general']['site_logo']) && !str_starts_with($siteSettings['general']['site_logo'], '/')) ? asset('storage/' . $siteSettings['general']['site_logo']) : asset($siteSettings['general']['site_logo'] ?? 'images/logo-sman1baleendah-32x32.png') }}">

        <!-- Preload critical hero image for better LCP -->
        <link rel="preload" as="image" href="{{ asset('images/hero-bg-sman1baleendah.jpeg') }}" type="image/jpeg" fetchpriority="high">

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
