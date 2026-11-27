@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    // Same list as the navbar (footer repeats it so the two can differ later if needed).
    $explore = collect([
        ['label' => 'About', 'href' => route('home').'#about'],
        ['label' => 'What we do', 'href' => route('home').'#what-we-do'],
        ['label' => 'Services', 'href' => route('home').'#services'],
        ['label' => 'Gallery', 'href' => route('home').'#gallery'],
        config('ministry.show_team') ? ['label' => 'Team', 'href' => route('home').'#team'] : null,
        ['label' => 'Join', 'href' => route('join.create')],
        ['label' => 'Contact', 'href' => route('home').'#contact'],
    ])->filter()->values();

    $linkClasses = 'link-fader inline-flex min-h-11 max-w-full items-center gap-3 rounded-sm text-white/80 transition-colors duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal [overflow-wrap:anywhere]';
    $headingClasses = 'font-mono text-xs uppercase tracking-[0.12em] text-white/60';
@endphp

<footer class="bg-console text-white/80">
    <div class="mx-auto grid max-w-7xl gap-12 px-6 py-16 sm:grid-cols-2 lg:grid-cols-12 lg:gap-8 lg:px-8 lg:py-20">

        {{-- Brand --}}
        <div class="min-w-0 sm:col-span-2 lg:col-span-5">
            <div class="flex items-center gap-4">
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
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary-dark text-xs font-semibold tracking-wide text-white"
                    >COG</span>
                @endif
                <div class="min-w-0">
                    <p class="text-base font-bold text-white">{{ config('ministry.name') }}</p>
                    <p class="font-mono text-xs uppercase tracking-[0.12em] text-white/60">{{ config('ministry.subheader') }}</p>
                </div>
            </div>

            <p class="mt-6 max-w-sm text-base leading-relaxed text-white/80">We serve behind the sound.</p>
        </div>

        {{-- Explore --}}
        <nav aria-labelledby="footer-explore" class="min-w-0 lg:col-span-3 lg:col-start-7">
            <h2 id="footer-explore" class="{{ $headingClasses }}">Explore</h2>
            <ul class="mt-4 flex flex-col gap-1 text-sm">
                @foreach ($explore as $link)
                    <li>
                        <a href="{{ $link['href'] }}" class="{{ $linkClasses }}">{{ $link['label'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Contact (links come from config/ministry.php; empty values hide the item) --}}
        <nav aria-labelledby="footer-contact" class="min-w-0 lg:col-span-3 lg:col-start-10">
            <h2 id="footer-contact" class="{{ $headingClasses }}">Contact</h2>
            <ul class="mt-4 flex flex-col gap-1 text-sm">
                @if (config('ministry.facebook_url'))
                    <li>
                        <a
                            href="{{ config('ministry.facebook_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="{{ $linkClasses }}"
                        ><x-tabler-icon name="brand-facebook" class="h-4 w-4 text-white/60" />Facebook<span class="sr-only"> (opens in a new tab)</span></a>
                    </li>
                @endif

                @if (config('ministry.messenger_url'))
                    <li>
                        <a
                            href="{{ config('ministry.messenger_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="{{ $linkClasses }}"
                        ><x-tabler-icon name="message-circle" class="h-4 w-4 text-white/60" />Messenger<span class="sr-only"> (opens in a new tab)</span></a>
                    </li>
                @endif

                @if (config('ministry.email'))
                    <li>
                        {{-- <wbr> after "@" (on escaped text) lets a long address wrap cleanly instead of overflowing. --}}
                        <a href="mailto:{{ config('ministry.email') }}" class="{{ $linkClasses }} break-words"><x-tabler-icon name="mail" class="h-4 w-4 shrink-0 text-white/60" />{!! str_replace('@', '@<wbr>', e(config('ministry.email'))) !!}</a>
                    </li>
                @endif

                @if (filled(config('ministry.address')))
                    <li class="flex min-h-11 items-start gap-3 py-2">
                        <x-tabler-icon name="map-pin" class="mt-0.5 h-4 w-4 shrink-0 text-white/60" />
                        <span>{{ config('ministry.address') }}</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>

    <div class="border-t border-white/10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-xs text-white/60 sm:flex-row sm:items-center sm:justify-between lg:px-8">
            <p>&copy; {{ now()->year }} {{ config('ministry.name') }}. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <span class="font-mono uppercase tracking-[0.12em] text-white/60">CH OUT</span>
                <span aria-hidden="true" class="h-px w-8 bg-white/30"></span>
                <a
                    href="#main"
                    class="link-fader inline-flex min-h-11 items-center gap-2 rounded-sm font-medium text-white/80 transition-colors duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal"
                >
                    Back to top
                    <x-tabler-icon name="arrow-right" class="h-4 w-4 -rotate-90" />
                </a>
            </div>
        </div>
    </div>
</footer>
