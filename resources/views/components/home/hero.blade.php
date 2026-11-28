{{-- CH 00. Console hero (spec: brain/Features/hero-console-story.md). Always dark, so text uses white in both themes.
     Background: CSS red/blue streaks, grain and channel hairlines (the look of the cover photo, without its flat console).
     Stage: the live Three.js desk (resources/js/home/console-scene.js) on the right / lower right, blended over the
     background. The poster is a still render of the same desk: it shows with no JS, with reduced motion, and on
     devices that do not run the 3D. It is decorative, so alt is empty.
     Copy: panel 0 (the headline block, always readable) plus four chapter panels for the desktop scroll story
     (decorative, aria-hidden). The CTA row is shared by the headline and the last chapter.
     Red here is the scoped hero exception (brain/Decisions/hero-join-brand-exception.md). --}}
@props(['sundayServices' => 0])

@push('head')
    {{-- Below lg the wordmark is above the fold (likely the LCP): preload it there only. Desktop never fetches it. --}}
    @if (file_exists(public_path('images/logo-full.webp')))
        <link rel="preload" as="image" href="{{ asset('images/logo-full.webp') }}" type="image/webp" media="(max-width: 1023px)" fetchpriority="high">
    @endif
@endpush

@php
    $glb = file_exists(public_path('models/dlive.glb')) ? asset('models/dlive.glb') : null;
    $chapters = [
        ['label' => 'CH 01 — Power on', 'line' => 'Every Sunday, before the first word is spoken…'],
        ['label' => 'CH 02 — The fader', 'line' => '…someone makes sure it’s heard.'],
        ['label' => 'CH 03 — The mix', 'line' => 'Every voice. Every instrument. In balance.'],
    ];
@endphp

<section data-hero data-home-fx aria-labelledby="hero-title" class="relative isolate flex min-h-[100svh] flex-col overflow-hidden bg-console text-white">
    {{-- Background: two red streaks from the top right, one blue from the lower left, grain, channel hairlines. --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-20 overflow-hidden">
        <span class="hero-streak hero-streak-red"></span>
        <span class="hero-streak hero-streak-red-thin"></span>
        <span class="hero-streak hero-streak-blue"></span>
        <span class="hero-grain absolute inset-0"></span>
        <div class="absolute inset-0 hidden lg:block">
            <div class="mx-auto grid h-full max-w-7xl grid-cols-12 border-r border-white/[0.04] px-8">
                @for ($i = 0; $i < 12; $i++)
                    <span class="border-l border-white/[0.04]"></span>
                @endfor
            </div>
        </div>
    </div>

    {{-- The desk. Its own box on phones (above the copy); the whole hero on desktop, framed to the right. --}}
    <div
        data-hero-stage
        @if ($glb) data-glb="{{ $glb }}" @endif
        @if (app()->isLocal()) data-dev-3d @endif
        aria-hidden="true"
        class="hero-stage relative order-last -z-10 h-[42svh] w-full shrink-0 lg:absolute lg:inset-0 lg:h-auto"
    >
        {{-- Poster: still renders of the same desk at the "Silence" shot (black ground, so it screens onto the
             background like the canvas). Desktop: full-hero frame. Phones: the stage box frame. --}}
        <picture data-hero-poster class="pointer-events-none absolute inset-x-0 bottom-0 top-0 block lg:top-0">
            <source media="(min-width: 1024px)" type="image/webp" srcset="{{ asset('images/hero-console-1600.webp') }}">
            <source type="image/webp" srcset="{{ asset('images/hero-console-800.webp') }}">
            <img
                src="{{ asset('images/hero-console-1600.jpg') }}"
                alt=""
                width="1600"
                height="1000"
                fetchpriority="high"
                decoding="async"
                class="h-full w-full object-cover object-center max-lg:object-[50%_60%] lg:object-right-bottom"
            >
        </picture>

        <canvas class="block h-full w-full"></canvas>

        {{-- Loader: hidden until the 3D work starts (hero.js). Without JS it never appears. --}}
        <div data-hero-loader hidden class="pointer-events-none absolute inset-x-5 top-3 sm:inset-x-6 lg:bottom-20 lg:top-auto lg:left-[44%] lg:right-8">
            <div class="flex items-center gap-4 font-mono text-[11px] uppercase tracking-[0.12em] text-white/70">
                <p class="shrink-0">Loading surface <span data-hero-percent>000</span>%</p>
                <span class="relative h-px flex-1 overflow-hidden bg-white/20">
                    <span data-hero-meter class="absolute inset-0 origin-left bg-signal"></span>
                </span>
            </div>
        </div>
    </div>

    {{-- Desktop scrim: keeps the copy column readable when the camera moves in and the desk fills the frame. --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-y-0 left-0 hidden w-[62%] bg-gradient-to-r from-console/90 via-console/50 to-transparent lg:block"></div>

    {{-- Phones and tablets: the copy sits on top (order-first) and the desk below it. z-10 only from lg, so the
         wordmark's blend mode can reach the console background below lg. --}}
    <div class="relative order-first mx-auto flex w-full max-w-7xl flex-1 flex-col justify-start px-5 pb-4 pt-20 max-lg:pt-[5.5rem] sm:px-6 lg:z-10 lg:justify-center lg:px-8 lg:pb-28 lg:pt-28">
        <div class="lg:max-w-[34rem] xl:max-w-[38rem]">
            {{-- All panels share one grid cell, so the column keeps the height of the tallest (the headline block). --}}
            <div class="grid">
                <div data-hero-panel data-hero-content class="motion-safe:animate-fade-up [grid-area:1/1]">
                    <p class="t-label flex items-center gap-3 text-white/70">
                        <span class="relative flex h-2 w-2 shrink-0">
                            <span class="absolute inset-0 rounded-full bg-signal opacity-60 motion-safe:animate-ping"></span>
                            <span class="relative h-2 w-2 rounded-full bg-signal"></span>
                        </span>
                        <span>CH 00 — Church of God · {{ config('ministry.subheader') }}</span>
                    </p>

                    {{-- Phones and tablets: the name is sr-only (screen readers still get "COG Audio Ministry") and the
                         wordmark image shows beside it, aria-hidden. From lg the name is the visible headline. The image is
                         cropped to the wordmark (sizes in em; 1em = the height of "COG", 17.5vw clamped). Its contrast filter
                         makes the black ground pure black, so the screen blend hides it. --}}
                    <h1 id="hero-title" data-hero-title class="hero-title t-display break-words font-semibold tracking-[-0.035em] sr-only lg:not-sr-only lg:mt-6">{{ config('ministry.name') }}</h1>

                    <div aria-hidden="true" class="mt-4 lg:hidden">
                        <span class="relative block h-[2.16em] w-[5em] overflow-hidden text-[clamp(56px,17.5vw,84px)]">
                            {{-- Lazy on purpose: a display:none image (desktop) is not fetched, so the wordmark file costs nothing there. --}}
                            <img
                                src="{{ asset('images/logo-full.webp') }}"
                                alt=""
                                width="512"
                                height="512"
                                loading="lazy"
                                decoding="async"
                                class="absolute left-[-1.46em] top-[-2.9em] h-[7.87em] w-[7.87em] max-w-none mix-blend-screen [filter:contrast(3)]"
                            >
                        </span>
                        <span class="mt-1 block text-[13px] font-medium uppercase leading-none tracking-[0.35em] text-white">Ministry</span>
                    </div>

                    <p class="t-lead mt-4 font-semibold text-white lg:mt-5">{{ config('ministry.tagline') }}</p>

                    {{-- Theme verse (from config). Hidden when no verse text is set. Tablets and desktop only; phones read it in About. --}}
                    @if (filled(config('ministry.verse.text')))
                        <figure class="mt-7 hidden max-w-xl border-l border-signal/70 pl-5 md:block">
                            <blockquote class="text-base italic leading-relaxed text-white/90 sm:text-lg">
                                <p>&ldquo;{{ config('ministry.verse.text') }}&rdquo;</p>
                            </blockquote>
                            @if (filled(config('ministry.verse.reference')))
                                <figcaption class="mt-2 font-mono text-xs uppercase tracking-[0.12em] text-white/60">{{ config('ministry.verse.reference') }}</figcaption>
                            @endif
                        </figure>
                    @endif

                    <p class="mt-6 hidden max-w-xl text-base leading-relaxed text-white/75 lg:block sm:text-lg">
                        We serve our church through sound. Whether you mix, stream, or set up the stage, there is a place for you to serve with us.
                    </p>
                </div>

                {{-- Chapters 1-3 (desktop scroll story only). --}}
                @foreach ($chapters as $chapter)
                    <div data-hero-panel aria-hidden="true" class="hidden flex-col justify-center [grid-area:1/1] lg:invisible lg:flex">
                        <p class="t-label text-white/70">{{ $chapter['label'] }}</p>
                        <p data-hero-line class="t-display mt-6 font-semibold tracking-[-0.035em]">{{ $chapter['line'] }}</p>
                    </div>
                @endforeach

                {{-- Chapter 4, The room: sits right above the shared CTA row. --}}
                <div data-hero-panel aria-hidden="true" class="hidden flex-col justify-end [grid-area:1/1] lg:invisible lg:flex">
                    <p class="t-label text-white/70">CH 04 — The room</p>
                    <p data-hero-line class="t-display mt-6 font-semibold tracking-[-0.035em]">We serve behind the sound.</p>
                    <p class="t-lead mt-5 text-white/80">Serve with us. Halina at sumama sa amin!</p>
                </div>
            </div>

            <div data-hero-cta class="mt-14 flex w-full flex-col gap-3 md:w-auto md:flex-row md:items-center md:gap-4">
                <x-home.join-cta :href="route('join.create')" />
                <x-button :href="route('home').'#services'" variant="on-dark" class="w-full whitespace-nowrap md:w-auto">See Service Times</x-button>
            </div>
        </div>
    </div>

    {{-- Bottom readout rail: mono channel names, like the scribble strip under a desk's faders. Decorative. --}}
    <div aria-hidden="true" class="relative order-last z-10 hidden border-t border-white/10 sm:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-6 py-4 font-mono text-[11px] uppercase tracking-[0.14em] text-white/50 lg:px-8">
            <span>01 Front of house</span>
            <span>02 Monitors</span>
            <span class="hidden md:inline">03 Livestream</span>
            <span class="hidden md:inline">04 Recording</span>
            <span class="hidden md:inline">05 Stage &amp; Patching</span>
            @if ($sundayServices > 0)
                <span class="hidden text-white/70 lg:inline">{{ $sundayServices }} Sunday {{ \Illuminate\Support\Str::plural('service', $sundayServices) }}</span>
            @endif
        </div>
    </div>

    {{-- Scroll cue: a real link to the next section. Below lg it sits in the flow under the desk (never over it);
         from lg it is absolute at the bottom, as before. --}}
    <a
        href="#about"
        data-hero-cue
        aria-label="Scroll to About"
        class="relative order-last z-10 mx-auto mb-4 mt-6 flex h-11 w-11 shrink-0 items-center justify-center rounded-console text-white/80 transition-colors duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-console motion-safe:animate-nudge lg:absolute lg:inset-x-0 lg:bottom-14 lg:mt-0 lg:mb-0"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </a>
</section>
