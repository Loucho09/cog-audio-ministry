<section aria-labelledby="hero-title" class="relative isolate overflow-hidden bg-text">
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
            class="absolute inset-0 -z-10 h-full w-full object-cover object-center"
        >
    </picture>

    {{-- Even dark overlay so white text stays readable (WCAG AA) over any part of the photo. --}}
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-black/65"></div>

    {{-- The photo is always dark, so hero text uses white directly instead of theme tokens. --}}
    <div class="mx-auto flex min-h-[32rem] max-w-6xl items-center justify-center px-4 py-20 sm:min-h-[36rem] sm:px-6 sm:py-28 lg:px-8">
        <div class="flex flex-col items-center text-center">
            <h1 id="hero-title" class="text-4xl font-bold tracking-tight text-white sm:text-5xl">{{ config('ministry.name') }}</h1>
            <p class="mt-4 text-lg font-medium text-white sm:text-xl">{{ config('ministry.tagline') }}</p>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-white/85">
                We serve our church through sound. Whether you mix, stream, or set up the stage, there is a place for you to serve with us.
            </p>

            <div class="mt-8 flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:gap-4">
                {{-- TODO: point back to route('home').'#join' when the Join Us section exists (later Phase 1 slice). --}}
                <x-site.button :href="route('home').'#about'" size="lg" class="w-full sm:w-auto">Join Us</x-site.button>
                <x-site.button :href="route('home').'#services'" variant="secondary" size="lg" class="w-full sm:w-auto">See Service Times</x-site.button>
            </div>
        </div>
    </div>
</section>
