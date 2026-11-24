@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    $links = collect([
        ['label' => 'About', 'href' => route('home').'#about'],
        ['label' => 'What We Do', 'href' => route('home').'#what-we-do'],
        config('ministry.show_team') ? ['label' => 'Meet the Team', 'href' => route('home').'#team'] : null,
        ['label' => 'Services', 'href' => route('home').'#services'],
        ['label' => 'Gallery', 'href' => route('home').'#gallery'],
        // TODO: re-enable these three when the #join, #faq and #contact sections are built (set 'ready' => true).
        ['label' => 'Join Us', 'href' => route('home').'#join', 'ready' => false],
        ['label' => 'FAQ', 'href' => route('home').'#faq', 'ready' => false],
        ['label' => 'Contact', 'href' => route('home').'#contact', 'ready' => false],
    ])->filter(fn ($link) => $link && ($link['ready'] ?? true))->values();

    $authUrl = auth()->check() ? route('dashboard') : route('login');
    $authLabel = auth()->check() ? 'Dashboard' : 'Member Login';

    $linkClasses = 'inline-flex min-h-11 items-center whitespace-nowrap rounded-sm text-text/80 transition-colors duration-200 hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark';
@endphp

<header
    class="sticky top-0 z-50 border-b border-text/10 bg-surface/95 backdrop-blur"
    x-data="{ open: false }"
    @keydown.escape.window="if (open) { open = false; $refs.menuButton.focus() }"
>
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">

        {{-- Brand --}}
        <a
            href="{{ route('home') }}"
            class="flex min-h-11 min-w-0 flex-1 items-center gap-3 rounded-md transition-opacity duration-200 hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
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
                <span class="block truncate text-sm font-semibold text-text sm:text-base">{{ config('ministry.name') }}</span>
                <span class="block truncate text-xs text-text/60">{{ config('ministry.subheader') }}</span>
            </span>
        </a>

        <div class="flex shrink-0 items-center gap-4 xl:gap-6">

            {{-- Desktop links --}}
            <nav aria-label="Main" class="hidden shrink-0 lg:block">
                <ul class="flex items-center gap-4 text-sm font-medium xl:gap-6">
                    @foreach ($links as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="{{ $linkClasses }}">{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            {{-- Desktop auth button --}}
            <x-site.button :href="$authUrl" class="hidden lg:inline-flex">{{ $authLabel }}</x-site.button>

            {{-- Light/dark theme toggle (all screen sizes). The saved choice is applied early by the layout's head script. --}}
            <button
                type="button"
                data-theme-toggle
                x-data="{
                    dark: document.documentElement.classList.contains('dark'),
                    init() {
                        // Follow the device setting live until the visitor picks a theme.
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                            let saved = null;
                            try { saved = localStorage.getItem('theme'); } catch (err) {}
                            if (!saved) { this.apply(e.matches); }
                        });
                    },
                    apply(dark) {
                        this.dark = dark;
                        document.documentElement.classList.toggle('dark', dark);
                    },
                    toggle() {
                        this.apply(!this.dark);
                        try { localStorage.setItem('theme', this.dark ? 'dark' : 'light'); } catch (e) {}
                    },
                }"
                @click="toggle()"
                aria-label="Dark mode"
                aria-pressed="false"
                :aria-pressed="dark.toString()"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-text transition-colors duration-200 hover:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
            >
                {{-- Sun (shown in dark mode) --}}
                <svg x-show="dark" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                </svg>
                {{-- Moon (shown in light mode) --}}
                <svg x-show="!dark" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                </svg>
            </button>

            {{-- Mobile menu toggle --}}
            <button
                type="button"
                x-ref="menuButton"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="mobile-menu"
                aria-label="Toggle navigation menu"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-text transition-colors duration-200 hover:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark lg:hidden"
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

    {{-- Mobile menu (sized to the viewport below the 4rem header) --}}
    <div
        id="mobile-menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="max-h-[calc(100dvh-4rem)] overflow-y-auto border-t border-text/10 bg-surface lg:hidden"
    >
        <nav aria-label="Mobile" class="mx-auto max-w-6xl px-4 py-4 sm:px-6">
            <ul class="flex flex-col">
                @foreach ($links as $link)
                    <li>
                        <a
                            href="{{ $link['href'] }}"
                            @click="open = false"
                            class="block rounded-md px-3 py-3 text-base text-text transition-colors duration-200 hover:bg-background hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
                        >{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-4 border-t border-text/10 pt-4">
                <x-site.button :href="$authUrl" class="w-full">{{ $authLabel }}</x-site.button>
            </div>
        </nav>
    </div>
</header>
