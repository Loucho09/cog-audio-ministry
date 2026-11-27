{{-- CH 00. Photo hero (owner choice 2026-11-27: the cover photo hero comes back instead of the 3D desk).
     Full-bleed cover photo, always dark, so hero text uses white directly in both themes. The photo is decorative
     (the h1 carries the name), so alt is empty. Above the fold: no lazy loading.
     The frame caps the photo at 110rem / 2000px because the largest source is 1600px wide; past that the sides fade
     into the console background. Desktop pins the photo low so its own "COG Audio" wordmark sits right of the copy.
     The 3D desk files (resources/js/home/console-scene.js, dlive-surface.js) stay on disk, unused. --}}
@props(['sundayServices' => 0])

<section data-hero data-home-fx aria-labelledby="hero-title" class="relative isolate flex min-h-[100svh] flex-col overflow-hidden bg-console text-white">
    <div aria-hidden="true" class="absolute inset-y-0 left-1/2 -z-10 w-full max-w-[min(110rem,2000px)] -translate-x-1/2 min-[1981px]:[mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)]">
        <picture data-hero-poster>
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
                class="absolute inset-0 h-full w-full object-cover object-[30%_center] lg:inset-auto lg:bottom-0 lg:left-0 lg:h-auto lg:max-h-full lg:[mask-image:linear-gradient(to_top,black_70%,transparent)]"
            >
        </picture>
    </div>

    {{-- Readability scrim: dark under the copy on the left, open on the right so the photo's wordmark reads. --}}
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-r from-console/95 via-console/55 to-transparent"></div>
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-console via-transparent to-console/50"></div>

    {{-- Console texture: faint channel-strip hairlines (desktop), like the dividers on a mixing desk. --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 hidden lg:block">
        <div class="mx-auto grid h-full max-w-7xl grid-cols-12 border-r border-white/[0.04] px-8">
            @for ($i = 0; $i < 12; $i++)
                <span class="border-l border-white/[0.04]"></span>
            @endfor
        </div>
    </div>

    <div class="relative z-10 mx-auto flex w-full max-w-7xl flex-1 flex-col justify-end px-5 pb-28 pt-32 sm:px-6 lg:justify-center lg:px-8 lg:pb-32">
        <div data-hero-content class="motion-safe:animate-fade-up max-w-2xl">
            {{-- Channel label with a live LED: the console's "this channel is on" light. --}}
            <p class="t-label flex items-center gap-3 text-white/70">
                <span class="relative flex h-2 w-2 shrink-0">
                    <span class="absolute inset-0 rounded-full bg-signal motion-safe:animate-ping opacity-60"></span>
                    <span class="relative h-2 w-2 rounded-full bg-signal"></span>
                </span>
                <span>CH 00 — Church of God · {{ config('ministry.subheader') }}</span>
            </p>

            <h1 id="hero-title" data-hero-title class="hero-title t-display mt-6 break-words font-semibold tracking-[-0.035em]">{{ config('ministry.name') }}</h1>

            <p class="t-lead mt-5 font-semibold text-white">{{ config('ministry.tagline') }}</p>

            {{-- Theme verse (from config). Hidden when no verse text is set. --}}
            @if (filled(config('ministry.verse.text')))
                <figure class="mt-7 max-w-xl border-l border-signal/70 pl-5">
                    <blockquote class="text-base italic leading-relaxed text-white/90 sm:text-lg">
                        <p>&ldquo;{{ config('ministry.verse.text') }}&rdquo;</p>
                    </blockquote>
                    @if (filled(config('ministry.verse.reference')))
                        <figcaption class="mt-2 font-mono text-xs uppercase tracking-[0.12em] text-white/60">{{ config('ministry.verse.reference') }}</figcaption>
                    @endif
                </figure>
            @endif

            <p class="mt-6 max-w-xl text-base leading-relaxed text-white/75 sm:text-lg">
                We serve our church through sound. Whether you mix, stream, or set up the stage, there is a place for you to serve with us.
            </p>

            <div class="mt-9 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                <x-button :href="route('join.create')" class="w-full whitespace-nowrap sm:w-auto">Join Us</x-button>
                <x-button :href="route('home').'#services'" variant="on-dark" class="w-full whitespace-nowrap sm:w-auto">See Service Times</x-button>
            </div>
        </div>
    </div>

    {{-- Bottom readout rail: hairline plus mono channel names, like the scribble strip under a desk's faders.
         Decorative (the same roles are listed in What we do). --}}
    <div aria-hidden="true" class="relative z-10 hidden border-t border-white/10 sm:block">
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

    {{-- Scroll cue: a real link to the next section. --}}
    <a
        href="#about"
        data-hero-cue
        aria-label="Scroll to About"
        class="absolute inset-x-0 bottom-4 z-10 mx-auto flex h-11 w-11 items-center justify-center rounded-console text-white/80 transition-colors duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-console motion-safe:animate-nudge sm:bottom-14"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </a>
</section>
