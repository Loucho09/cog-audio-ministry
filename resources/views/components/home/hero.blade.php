{{-- CH 00. Dark console hero, in both themes. The 3D desk (resources/js/home/hero.js) draws on the right on wide
     screens and above the copy on phones. The cover photo is the poster: it shows with no JS, with reduced motion,
     or on a device without WebGL. It is decorative, so alt is empty.
     The copy is one panel plus two decorative panels (aria-hidden) that the scroll story crossfades on desktop. --}}
@props(['sundayServices' => 0])

@php
    $glb = file_exists(public_path('models/dlive.glb')) ? asset('models/dlive.glb') : null;
@endphp

<section data-hero data-home-fx aria-labelledby="hero-title" class="relative isolate flex min-h-[100svh] flex-col overflow-hidden bg-console text-white">
    {{-- Poster: the cover photo, cooler and darkened so the copy keeps its contrast. --}}
    <div data-hero-photo aria-hidden="true" class="absolute inset-0 -z-10">
        <picture class="block h-full w-full">
            <source
                type="image/webp"
                srcset="{{ asset('images/cover-800.webp') }} 800w, {{ asset('images/cover-1200.webp') }} 1200w, {{ asset('images/cover.webp') }} 1600w"
                sizes="100vw"
            >
            <img
                src="{{ asset('images/cover.jpg') }}"
                alt=""
                width="1600"
                height="609"
                fetchpriority="high"
                decoding="async"
                class="h-full w-full object-cover object-[60%_center]"
            >
        </picture>
        <div class="absolute inset-0 bg-console/80 lg:hidden"></div>
        <div class="absolute inset-0 hidden bg-gradient-to-r from-console via-console/70 to-transparent lg:block"></div>
    </div>

    {{-- The 3D desk. Its own box on phones (above the copy); the right side of the hero on desktop. --}}
    <div
        data-hero-stage
        @if ($glb) data-glb="{{ $glb }}" @endif
        aria-hidden="true"
        class="hero-stage relative h-[50svh] w-full shrink-0 pt-16 lg:absolute lg:inset-y-0 lg:left-[42%] lg:right-0 lg:h-auto lg:w-auto lg:pt-0"
    >
        <canvas class="block h-full w-full"></canvas>

        {{-- Loader: hidden until the 3D work starts (hero.js). Without JS it never appears. --}}
        <div data-hero-loader hidden class="pointer-events-none absolute inset-x-5 bottom-5 sm:inset-x-6">
            <div class="flex items-center gap-4 font-mono text-[11px] uppercase tracking-[0.12em] text-white/70">
                <p class="shrink-0">Loading surface <span data-hero-percent>000</span>%</p>
                <span class="relative h-px flex-1 overflow-hidden bg-white/20">
                    <span data-hero-meter class="absolute inset-0 origin-left bg-signal"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col justify-end px-5 pb-20 pt-28 sm:px-6 sm:pt-10 lg:min-h-[100svh] lg:justify-center lg:px-8 lg:pb-16 lg:pt-28">
        <div class="relative lg:w-5/12">
            {{-- Panel 1: the copy that is always there (no JS, reduced motion, and the top of the scroll). --}}
            <div data-hero-panel class="relative">
                <div data-hero-content class="motion-safe:animate-fade-up">
                    <p class="t-label text-white/70">CH 00 — COG Audio Ministry · {{ config('ministry.subheader') }}</p>

                    <h1 id="hero-title" data-hero-title class="hero-title t-display mt-6 break-words">We serve behind the sound.</h1>

                    <p class="t-lead mt-6 max-w-xl text-white/80">{{ config('ministry.tagline') }}</p>

                    {{-- Theme verse (from config). Hidden when no verse text is set. --}}
                    @if (filled(config('ministry.verse.text')))
                        <figure class="mt-6 max-w-xl border-l border-white/25 pl-5">
                            <blockquote class="text-base italic leading-relaxed text-white/90 sm:text-lg">
                                <p>&ldquo;{{ config('ministry.verse.text') }}&rdquo;</p>
                            </blockquote>
                            @if (filled(config('ministry.verse.reference')))
                                <figcaption class="mt-2 font-mono text-xs uppercase tracking-[0.12em] text-white/60">{{ config('ministry.verse.reference') }}</figcaption>
                            @endif
                        </figure>
                    @endif

                    <div class="mt-9 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                        <x-button :href="route('join.create')" class="w-full sm:w-auto">Join Us</x-button>
                        <x-button :href="route('home').'#services'" variant="on-dark" class="w-full sm:w-auto">See Service Times</x-button>
                    </div>
                </div>
            </div>

            {{-- Panel 2 (desktop only, scroll story). Decorative: the same services are listed below. --}}
            <div data-hero-panel aria-hidden="true" class="hidden lg:invisible lg:absolute lg:inset-0 lg:flex lg:flex-col lg:justify-center">
                <p class="t-display">Every service, every voice — clearly heard.</p>
                @if ($sundayServices > 0)
                    <p class="t-lead mt-6 max-w-xl text-white/80">{{ $sundayServices }} Sunday {{ \Illuminate\Support\Str::plural('service', $sundayServices) }}. One team.</p>
                @endif
            </div>

            {{-- Panel 3 (desktop only, scroll story): the call to action. Text only, so nothing focusable is hidden. --}}
            <div data-hero-panel aria-hidden="true" class="hidden lg:invisible lg:absolute lg:inset-0 lg:flex lg:flex-col lg:justify-center">
                <p class="t-display">Serve with us.</p>
                <p class="t-lead mt-6 max-w-xl text-white/80">Halina at sumama sa amin!</p>
            </div>
        </div>
    </div>

    {{-- Scroll cue: a real link to the next section. White on the dark hero. --}}
    <a
        href="#about"
        data-hero-cue
        aria-label="Scroll to About"
        class="absolute inset-x-0 bottom-4 z-10 mx-auto flex h-11 w-11 items-center justify-center rounded-console text-white/85 transition-colors duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-console motion-safe:animate-nudge"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </a>
</section>
