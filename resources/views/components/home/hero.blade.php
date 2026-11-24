<section aria-labelledby="hero-title" class="relative isolate flex min-h-[100svh] items-center overflow-hidden bg-black sm:min-h-[44rem] lg:min-h-[clamp(44rem,85svh,52rem)]">
    {{-- Cover photo as a full-bleed background. Decorative (the h1 carries the name), so alt is empty.
         Above the fold, so no lazy loading. --}}
    <picture>
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
            class="absolute inset-0 -z-10 h-full w-full object-cover object-center lg:bottom-auto lg:h-auto lg:max-h-full lg:[mask-image:linear-gradient(to_bottom,black_65%,transparent)]"
        >
        {{-- Desktop: fit the photo to the width (not cover) so the wide photo is not cropped on the left,
             which pushed its wordmark behind the hero text at ~1440px. The bottom fades into the black section. --}}
    </picture>

    {{-- Readability overlay. Darker on the left where the text sits; the photo's wordmark stays visible on the right.
         The photo is always dark, so hero text uses white directly instead of theme tokens. --}}
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-r from-black/90 via-black/70 to-black/50 lg:via-black/60"></div>
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-black/80 via-transparent to-black/30"></div>

    {{-- Stage-light glows in the brand colours. --}}
    <x-site.glow tone="primary" class="-left-40 top-10 h-[28rem] w-[28rem] opacity-70 sm:-left-24" />
    {{-- Accent glow only in dark mode: in light mode it smears pink into the light section below. --}}
    <x-site.glow tone="accent" class="-bottom-24 right-0 hidden h-80 w-80 opacity-70 dark:block sm:right-10" />

    {{-- Dark mode: bottom fade into the next section (background token), so the photo does not end in a hard edge.
         Light mode: no fade (dark-to-light fades look blurry); the photo ends crisply on a thin stage-colour line. Decorative only. --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 hidden h-32 bg-gradient-to-t from-background to-transparent dark:block"></div>
    <span aria-hidden="true" class="absolute inset-x-0 bottom-0 h-0.5 bg-gradient-to-r from-primary via-primary to-accent dark:hidden"></span>

    <div class="relative mx-auto w-full max-w-7xl px-5 pb-24 pt-28 sm:px-6 sm:pt-32 lg:px-8">
        <div class="motion-safe:animate-fade-up flex flex-col items-center text-center lg:items-start lg:text-left">

            {{-- Small pill above the title: EQ bars and the location line. --}}
            <p class="inline-flex max-w-full items-center gap-3 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-[0.2em] text-white backdrop-blur-sm">
                <x-site.eq-bars />
                <span class="min-w-0 break-words">Church of God &middot; {{ config('ministry.subheader') }}</span>
            </p>

            {{-- "Audio" is set in the script font to echo the logo. The name is escaped first, so the replace is safe. --}}
            <h1 id="hero-title" class="mt-6 max-w-full break-words text-5xl font-extrabold leading-[1.05] tracking-tight text-white sm:text-6xl lg:text-7xl 2xl:text-8xl">{!!str_replace('Audio', '<span class="inline-block bg-gradient-to-r from-white to-accent bg-clip-text pb-1 font-script text-[1.9em] font-normal leading-none tracking-normal text-transparent forced-colors:bg-none forced-colors:text-[CanvasText]">Audio</span>', e(config('ministry.name'))) !!}</h1>

            <p class="mt-5 text-lg font-semibold text-white sm:text-xl 2xl:text-2xl">{{ config('ministry.tagline') }}</p>

            {{-- Theme verse (from config, as on the Facebook page). Red bar echoes the section eyebrows. --}}
            @if (filled(config('ministry.verse.text')))
                <figure class="mt-6 max-w-xl">
                    <span aria-hidden="true" class="mx-auto mb-4 block h-0.5 w-10 rounded-full bg-accent lg:mx-0"></span>
                    <blockquote class="text-base italic leading-relaxed text-white sm:text-lg">
                        <p>&ldquo;{{ config('ministry.verse.text') }}&rdquo;</p>
                    </blockquote>
                    @if (filled(config('ministry.verse.reference')))
                        <figcaption class="mt-2 text-xs font-semibold uppercase tracking-[0.2em] text-white/90">{{ config('ministry.verse.reference') }}</figcaption>
                    @endif
                </figure>
            @endif
            <p class="mt-4 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">
                We serve our church through sound. Whether you mix, stream, or set up the stage, there is a place for you to serve with us.
            </p>

            <div class="mt-9 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                <x-site.button :href="route('home').'#join'" size="lg" class="w-full sm:w-auto">Join Us</x-site.button>
                <x-site.button :href="route('home').'#services'" variant="outline-light" size="lg" class="w-full sm:w-auto">See Service Times</x-site.button>
            </div>
        </div>
    </div>

    {{-- Scroll cue: a real link to the next section. It sits on the dark photo, so white (the dark-mode fade ends near-black too). The bounce is motion-safe only. --}}
    <a
        href="#about"
        aria-label="Scroll to About"
        class="absolute inset-x-0 bottom-4 mx-auto flex h-11 w-11 items-center justify-center rounded-full text-white/85 transition-colors duration-200 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-black motion-safe:animate-nudge"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
    </a>
</section>
