<section aria-labelledby="hero-title" class="relative overflow-hidden bg-background">
    {{-- Decorative accents (tokens only, no images) --}}
    <div aria-hidden="true" class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-accent/10 blur-2xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -left-16 bottom-0 h-48 w-48 rounded-full bg-primary/10 blur-2xl"></div>

    <div class="relative mx-auto max-w-6xl px-4 pb-16 pt-20 sm:px-6 sm:pb-20 sm:pt-28 lg:px-8">
        <div class="flex flex-col items-center text-center">
            <h1 id="hero-title" class="text-4xl font-bold tracking-tight text-text sm:text-5xl">{{ config('ministry.name') }}</h1>
            <p class="mt-4 text-lg font-medium text-text sm:text-xl">{{ config('ministry.tagline') }}</p>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-text/70">
                We serve our church through sound. Whether you mix, stream, or set up the stage, there is a place for you to serve with us.
            </p>

            <div class="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                {{-- TODO: point back to route('home').'#join' when the Join Us section exists (later Phase 1 slice). --}}
                <x-site.button :href="route('home').'#about'" size="lg" class="w-full sm:w-auto">Join Us</x-site.button>
                <x-site.button :href="route('home').'#services'" variant="secondary" size="lg" class="w-full sm:w-auto">See Service Times</x-site.button>
            </div>
        </div>

        {{-- Cover banner, below the text and buttons. Above the fold on desktop, so no lazy loading. --}}
        <div class="mt-12 overflow-hidden rounded-2xl shadow-lg sm:mt-16">
            <picture>
                <source
                    type="image/webp"
                    srcset="{{ asset('images/cover-800.webp') }} 800w, {{ asset('images/cover-1200.webp') }} 1200w, {{ asset('images/cover.webp') }} 1600w"
                    sizes="(min-width: 1152px) 1152px, 100vw"
                >
                <img
                    src="{{ asset('images/cover.jpg') }}"
                    alt="COG Audio banner showing a lit mixing console beside the COG Audio logo"
                    width="1600"
                    height="609"
                    fetchpriority="high"
                    decoding="async"
                    class="block h-auto w-full"
                >
            </picture>
        </div>
    </div>
</section>
