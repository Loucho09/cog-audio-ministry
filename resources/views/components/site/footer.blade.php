@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    // Same list as the navbar (footer repeats it so the two can differ later if needed).
    $explore = collect([
        ['label' => 'About', 'href' => route('home').'#about'],
        ['label' => 'What We Do', 'href' => route('home').'#what-we-do'],
        config('ministry.show_team') ? ['label' => 'Meet the Team', 'href' => route('home').'#team'] : null,
        ['label' => 'Services', 'href' => route('home').'#services'],
        ['label' => 'Gallery', 'href' => route('home').'#gallery'],
        ['label' => 'Join Us', 'href' => route('home').'#join'],
        ['label' => 'Contact', 'href' => route('home').'#contact'],
    ])->filter()->values();

    $linkClasses = 'inline-flex min-h-11 max-w-full items-center [overflow-wrap:anywhere] rounded-sm text-text/80 transition-colors duration-200 hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark';
    $headingClasses = 'text-xs font-semibold uppercase tracking-[0.2em] text-text/60';
@endphp

<footer class="relative isolate overflow-hidden border-t border-text/10 bg-surface">
    <x-site.glow tone="accent" class="-bottom-32 -right-24 h-72 w-72 opacity-60" />

    <div class="mx-auto grid max-w-6xl gap-12 px-5 py-14 sm:px-6 sm:py-16 md:grid-cols-[1.4fr_1fr_1fr] lg:gap-16 lg:px-8">

        {{-- Brand --}}
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                @if ($hasLogo)
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt=""
                        width="48"
                        height="48"
                        loading="lazy"
                        decoding="async"
                        class="h-12 w-12 shrink-0 rounded-full object-contain"
                    >
                @else
                    {{-- Placeholder until public/images/logo.png is added --}}
                    <span
                        aria-hidden="true"
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-dark text-xs font-semibold tracking-wide text-surface"
                    >COG</span>
                @endif
                <div class="min-w-0">
                    <p class="text-base font-bold text-text">{{ config('ministry.name') }}</p>
                    <p class="text-sm text-text/60">{{ config('ministry.subheader') }}</p>
                </div>
            </div>

            <p class="mt-5 max-w-sm text-sm leading-relaxed text-text/80">{{ config('ministry.tagline') }}</p>
            <x-site.eq-bars class="mt-5" />
        </div>

        {{-- Explore --}}
        <nav aria-labelledby="footer-explore" class="min-w-0">
            <h2 id="footer-explore" class="{{ $headingClasses }}">Explore</h2>
            <ul class="mt-4 grid grid-cols-2 gap-x-6 text-sm">
                @foreach ($explore as $link)
                    <li>
                        <a href="{{ $link['href'] }}" class="{{ $linkClasses }}">{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Connect (links come from config/ministry.php; empty values hide the link) --}}
        <nav aria-labelledby="footer-connect" class="min-w-0">
            <h2 id="footer-connect" class="{{ $headingClasses }}">Connect</h2>
            <ul class="mt-4 flex flex-col gap-1 text-sm">
                @if (config('ministry.facebook_url'))
                    <li>
                        <a
                            href="{{ config('ministry.facebook_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="{{ $linkClasses }}"
                        >Facebook<span class="sr-only"> (opens in a new tab)</span></a>
                    </li>
                @endif

                @if (config('ministry.messenger_url'))
                    <li>
                        <a
                            href="{{ config('ministry.messenger_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="{{ $linkClasses }}"
                        >Messenger<span class="sr-only"> (opens in a new tab)</span></a>
                    </li>
                @endif

                @if (config('ministry.email'))
                    <li>
                        <a href="mailto:{{ config('ministry.email') }}" class="{{ $linkClasses }}">{{ config('ministry.email') }}</a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>

    <div class="border-t border-text/10">
        <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-5 py-6 text-xs text-text/60 sm:flex-row sm:px-6 lg:px-8">
            <p>&copy; {{ now()->year }} {{ config('ministry.name') }}. All rights reserved.</p>
            <a
                href="#main"
                class="inline-flex min-h-11 items-center gap-2 rounded-sm font-medium text-text/80 transition-colors duration-200 hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
            >
                Back to top
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                </svg>
            </a>
        </div>
    </div>
</footer>
