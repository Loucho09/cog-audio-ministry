<section aria-labelledby="hero-title" class="relative overflow-hidden bg-gradient-to-b from-background to-surface">
    {{-- Decorative accents (tokens only, no images) --}}
    <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent/10 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -left-24 bottom-0 h-72 w-72 rounded-full bg-primary/10 blur-3xl"></div>

    <div class="relative mx-auto max-w-6xl px-4 pb-16 pt-20 sm:px-6 sm:pb-20 sm:pt-28 lg:px-8">
        <div class="flex flex-col items-center text-center">
            <p class="text-sm font-medium uppercase tracking-wider text-primary-dark">{{ config('ministry.subheader') }}</p>
            <h1 id="hero-title" class="mt-3 text-4xl font-bold tracking-tight text-text sm:text-5xl">{{ config('ministry.name') }}</h1>
            <p class="mt-4 text-lg font-medium text-text sm:text-xl">{{ config('ministry.tagline') }}</p>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-text/70">
                We serve our church through sound. Whether you mix, stream, or set up the stage, there is a place for you to serve with us.
            </p>

            <div class="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                <x-site.button :href="route('home').'#join'" size="lg" class="w-full sm:w-auto">Join Us</x-site.button>
                <x-site.button :href="route('home').'#services'" variant="secondary" size="lg" class="w-full sm:w-auto">See Service Times</x-site.button>
            </div>
        </div>

        {{-- Cover banner, below the text and buttons. Above the fold on desktop, so no lazy loading. --}}
        <div class="mt-12 overflow-hidden rounded-2xl shadow-lg sm:mt-16">
            <picture>
                <source srcset="{{ asset('images/cover.webp') }}" type="image/webp">
                <img
                    src="{{ asset('images/cover.jpg') }}"
                    alt="COG Audio banner showing a lit mixing console beside the COG Audio logo"
                    width="1600"
                    height="609"
                    fetchpriority="high"
                    class="block h-auto w-full"
                >
            </picture>
        </div>
    </div>
</section>
