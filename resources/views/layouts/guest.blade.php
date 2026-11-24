@props([
    // Shown as the card heading (h1) and in the page <title>. Null falls back to the ministry name.
    'title' => null,
    // Opt-in split screen (desktop only): cover photo panel on the left, form on the right. Used by login only.
    'split' => false,
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

        <div @class(['flex flex-1 flex-col', 'lg:flex-row' => $split])>

            @if ($split)
                {{-- Photo panel: desktop only. Same cover photo and alt approach as the homepage hero (decorative, so alt is empty). --}}
                <aside class="relative isolate hidden overflow-hidden bg-black lg:flex lg:w-1/2 lg:flex-col lg:justify-end lg:p-12 xl:p-16">
                    <picture>
                        <source
                            type="image/webp"
                            srcset="{{ asset('images/cover-1200.webp') }} 1200w, {{ asset('images/cover.webp') }} 1600w"
                            sizes="50vw"
                        >
                        <img
                            src="{{ asset('images/cover.jpg') }}"
                            alt=""
                            width="1600"
                            height="609"
                            decoding="async"
                            class="absolute inset-0 -z-10 h-full w-full object-cover object-center"
                        >
                    </picture>
                    {{-- The photo is always dark, so text on it uses white directly, not theme tokens. --}}
                    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-black/85 via-black/60 to-black/40"></div>

                    <div class="relative max-w-md">
                        <h2 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-white xl:text-5xl">{!! str_replace('Audio', '<span class="font-script text-[1.4em] font-normal leading-none tracking-normal">Audio</span>', e($siteName)) !!}</h2>
                        <p class="mt-4 text-lg text-white/90">Welcome back. Thank you for serving with us.</p>
                    </div>
                </aside>
            @endif

            <div @class([
                'flex flex-1 flex-col items-center px-5 pb-10 pt-6 sm:px-6 sm:pt-12',
                'lg:w-1/2 lg:justify-center lg:px-12 lg:py-12 xl:px-16' => $split,
            ])>

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
        </div>
    </body>
</html>
