@props([
    // Page title for the browser tab (e.g. "Dashboard"). Null falls back to the ministry name.
    'title' => null,
])

@php
    $siteName = config('ministry.name');
    $pageTitle = $title ? $title.' | '.$siteName : $siteName;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex">

        <x-site.theme-script />

        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $siteName }}, {{ config('ministry.subheader') }}.">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=montserrat:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts / Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="flex min-h-screen flex-col bg-background font-sans text-text antialiased">

        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[60] focus:rounded-md focus:bg-primary-dark focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-surface focus:outline-none focus:ring-2 focus:ring-accent"
        >
            Skip to content
        </a>

        @include('layouts.navigation')

        <main id="main" class="flex-1">
            <div class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-10 lg:px-8">
                @isset($header)
                    <div class="mb-6">
                        {{ $header }}
                    </div>
                @endisset

                {{ $slot }}
            </div>
        </main>
    </body>
</html>
