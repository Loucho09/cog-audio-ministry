@props(['transparent' => false])

@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    // Join carries the blue signal LED. Labels are mono; hover uses the fader underline, not colour.
    $links = collect([
        ['label' => 'About', 'href' => route('home').'#about'],
        ['label' => 'What we do', 'href' => route('home').'#what-we-do'],
        ['label' => 'Services', 'href' => route('home').'#services'],
        ['label' => 'Gallery', 'href' => route('home').'#gallery'],
        config('ministry.show_team') ? ['label' => 'Team', 'href' => route('home').'#team'] : null,
        ['label' => 'Join', 'href' => route('join.create'), 'led' => true],
        ['label' => 'Contact', 'href' => route('home').'#contact'],
    ])->filter(fn ($link) => (bool) $link)->values();

    $authUrl = auth()->check() ? route('dashboard') : route('login');
    $authLabel = auth()->check() ? 'Dashboard' : 'Member Login';

    // Colours follow the header's data-solid state: text-text on a solid bar, white over the transparent hero.
    // The header is the `group`, so these group-data variants apply to every child.
    $linkClasses = 'link-fader relative inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-sm font-mono text-xs uppercase tracking-[0.12em] text-text/80 transition-colors duration-200 hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal group-data-[solid=false]:text-white/85 group-data-[solid=false]:hover:text-white group-data-[solid=false]:focus-visible:ring-white';

    $iconButtonClasses = 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-console text-text transition-colors duration-200 hover:bg-text/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal group-data-[solid=false]:text-white group-data-[solid=false]:hover:bg-white/10 group-data-[solid=false]:focus-visible:ring-white';
@endphp

<header
    class="group z-50 w-full border-b border-transparent transition-[background-color,border-color] duration-300 data-[solid=true]:border-text/10 data-[solid=true]:bg-background/90 data-[solid=true]:backdrop-blur-sm data-[dark=true]:border-white/10 data-[dark=true]:bg-console/90 data-[dark=true]:backdrop-blur-sm {{ $transparent ? 'fixed inset-x-0 top-0' : 'sticky top-0' }}"
    data-solid="{{ $transparent ? 'false' : 'true' }}"
    data-dark="false"
    x-data="{
        open: false,
        transparent: {{ $transparent ? 'true' : 'false' }},
        solid: {{ $transparent ? 'false' : 'true' }},
        dark: false,
        sync() {
            const scrolled = window.scrollY > 24;
            // Over the dark hero (pinned or still in view) the bar stays dark; the light bar starts only after leaving it.
            const hero = document.querySelector('[data-hero]');
            const inHero = hero ? hero.getBoundingClientRect().bottom > 64 : false;
            this.solid = !this.transparent || (scrolled && !inHero);
            this.dark = this.transparent && scrolled && inHero;
        },
    }"
    x-init="sync()"
    @scroll.window="sync()"
    :data-solid="(solid || open).toString()"
    :data-dark="(dark && !open).toString()"
    @keydown.escape.window="if (open) { open = false; $refs.menuButton.focus() }"
>
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-5 sm:px-6 lg:px-8">

        {{-- Brand --}}
        <a
            href="{{ route('home') }}"
            class="flex min-h-11 min-w-0 flex-1 items-center gap-3 rounded-sm transition-opacity duration-200 hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal"
        >
            @if ($hasLogo)
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt=""
                    width="40"
                    height="40"
                    class="h-10 w-10 shrink-0 rounded-full object-contain"
                >
            @else
                {{-- Placeholder until public/images/logo.png is added --}}
                <span
                    aria-hidden="true"
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-dark text-xs font-semibold tracking-wide text-surface"
                >COG</span>
            @endif

            <span class="min-w-0 leading-tight">
                <span class="block truncate text-[0.8125rem] font-bold tracking-tight text-text group-data-[solid=false]:text-white sm:text-base">{{ config('ministry.name') }}</span>
                <span class="block truncate font-mono text-[0.6875rem] uppercase tracking-[0.12em] text-text/70 group-data-[solid=false]:text-white/75">{{ config('ministry.subheader') }}</span>
            </span>
        </a>

        <div class="flex shrink-0 items-center gap-3 xl:gap-5">

            {{-- Desktop links (xl and up: seven labels need the room) --}}
            <nav aria-label="Main" class="hidden shrink-0 xl:block">
                <ul class="flex items-center gap-4 xl:gap-6">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="{{ $linkClasses }}">
                                {{ $link['label'] }}
                                @if (! empty($link['led']))
                                    <span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-signal"></span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Desktop auth button --}}
            <x-site.button :href="$authUrl" class="hidden xl:inline-flex">{{ $authLabel }}</x-site.button>

            {{-- Light/dark theme toggle (all screen sizes). The saved choice is applied early by the layout's head script. --}}
            <x-site.theme-toggle />

            {{-- Mobile menu toggle --}}
            <button
                type="button"
                x-ref="menuButton"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="mobile-menu"
                aria-label="Toggle navigation menu"
                class="{{ $iconButtonClasses }} xl:hidden"
            >
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu: always a solid surface panel, sized to the viewport below the 4rem header. --}}
    <div
        id="mobile-menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-text/10 bg-background xl:hidden"
    >
        <nav aria-label="Mobile" class="mx-auto max-w-7xl px-5 py-4 sm:px-6">
            <ul class="flex flex-col gap-1">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ $link['href'] }}"
                            @click="open = false"
                            class="flex min-h-11 items-center gap-2 rounded-console px-4 py-3.5 font-mono text-sm uppercase tracking-[0.12em] text-text transition-colors duration-200 hover:bg-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal"
                        >{{ $link['label'] }}@if (! empty($link['led']))<span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-signal"></span>@endif</a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 border-t border-text/10 pt-4">
                <x-site.button :href="$authUrl" size="lg" class="w-full">{{ $authLabel }}</x-site.button>
            </div>
        </nav>
    </div>
</header>
