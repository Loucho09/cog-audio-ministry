@props([
    'title' => null,
    'description' => null,
    // Navbar floats over a dark hero and turns solid on scroll. Defaults to true on the homepage only.
    'transparentNav' => null,
])

@php
    $siteName = config('ministry.name');
    $pageTitle = $title ? $title.' | '.$siteName : $siteName;
    $metaDescription = filled($description) ? $description : $siteName.', '.config('ministry.subheader').'.';
    $transparentNav = $transparentNav ?? request()->routeIs('home');
@endphp

<!DOCTYPE html>
<html lang="en" class="scroll-pt-20 motion-safe:scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <x-site.theme-script />

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <!-- Favicons (resized from the logo) -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

        <!-- Open Graph -->
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/cover-1200.webp') }}">
        <meta property="og:site_name" content="{{ $siteName }}">

        <!-- Twitter / X -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ asset('images/cover-1200.webp') }}">

        <!-- Fonts: Schibsted Grotesk (headings, body) and JetBrains Mono (labels, readouts) -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=schibsted-grotesk:400,500,600,700,800|jetbrains-mono:400,500&display=swap" rel="stylesheet" />

        <!-- Scripts / Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Hide Alpine-controlled elements until Alpine initialises -->
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="flex min-h-screen flex-col bg-background font-sans text-text antialiased">
        <x-site.page-progress />

        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-primary-dark focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-surface focus:outline-none focus:ring-2 focus:ring-accent"
        >
            Skip to content
        </a>

        <x-site.navbar :transparent="$transparentNav" />

        <main id="main" class="flex-1">
            {{ $slot }}
        </main>

        <x-site.footer />
    </body>
</html>
