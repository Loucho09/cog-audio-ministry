@props([
    // Shown as the card heading (h1) and in the page <title>. Null falls back to the ministry name.
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

        <div class="flex flex-1 flex-col items-center px-5 pb-10 pt-6 sm:px-6 sm:pt-12">

            {{-- Brand header: logo and name link back to the homepage --}}
            <header class="flex w-full max-w-md items-center justify-between gap-4">
                <a
                    href="{{ route('home') }}"
                    class="flex min-h-11 min-w-0 items-center gap-3 rounded-md transition-opacity duration-200 hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
                >
                    <x-site.logo />
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-base font-bold tracking-tight text-text">{{ $siteName }}</span>
                        <span class="block truncate text-xs text-text/70">{{ config('ministry.subheader') }}</span>
                    </span>
                </a>

                <x-site.theme-toggle />
            </header>

            <main id="main" class="mt-6 w-full max-w-md sm:mt-8">
                <div class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                    @if (filled($title))
                        <h1 class="text-2xl font-bold tracking-tight text-text">{{ $title }}</h1>
                    @endif

                    <div @class(['mt-6' => filled($title)])>
                        {{ $slot }}
                    </div>
                </div>

                <p class="mt-6 text-center">
                    <a
                        href="{{ route('home') }}"
                        class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-primary-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
                    >&larr; Back to homepage</a>
                </p>
            </main>
        </div>
    </body>
</html>
