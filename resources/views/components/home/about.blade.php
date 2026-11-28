{{-- CH 01. Readouts are live counts only (the years stat shows only when set in config/ministry.php). --}}
@props(['servicesPerWeek' => 0, 'churchesCount' => 0])

@php
    $stats = [
        ['value' => (int) $churchesCount, 'label' => 'Churches served'],
        ['value' => (int) $servicesPerWeek, 'label' => 'Services every week'],
        ['value' => (int) config('ministry.years_serving'), 'label' => 'Years serving'],
    ];
    $stats = array_values(array_filter($stats, fn ($stat) => $stat['value'] > 0));
@endphp

<x-section id="about" data-reveal>
    <div data-fx="fader-heading">
        <x-section-heading
            id="about"
            channel="01"
            eyebrow="About"
            title="Every service has a crew you never see."
        />
    </div>

    {{-- One editorial block: the statement runs cols 1-7; vision and mission stack in cols 9-12 with a hairline. --}}
    <div class="mt-12 grid gap-10 lg:mt-16 lg:grid-cols-12 lg:gap-8">
        <p class="text-xl leading-relaxed text-text/80 sm:text-2xl lg:col-span-7 lg:text-[1.75rem] lg:leading-snug">
            The Audio Ministry cares for the sound, stage, and livestream of our services in Dasmariñas, so worship can be heard and shared by everyone in the room and at home.
        </p>

        <div class="grid gap-8 border-t border-text/10 pt-8 sm:grid-cols-2 sm:gap-6 lg:col-span-4 lg:col-start-9 lg:block lg:border-l lg:border-t-0 lg:pl-8 lg:pt-0">
            <article aria-labelledby="vision-title" class="lg:border-b lg:border-text/10 lg:pb-8">
                <p aria-hidden="true" class="t-label text-text/60">V — 01</p>
                <h3 id="vision-title" class="mt-3 text-xl font-semibold tracking-tight">Our Vision</h3>
                {{-- TODO: vision/mission from the Audio Head --}}
                <p class="mt-3 leading-relaxed text-text/70">Coming soon.</p>
            </article>
            <article aria-labelledby="mission-title" class="lg:pt-8">
                <p aria-hidden="true" class="t-label text-text/60">M — 02</p>
                <h3 id="mission-title" class="mt-3 text-xl font-semibold tracking-tight">Our Mission</h3>
                {{-- TODO: vision/mission from the Audio Head --}}
                <p class="mt-3 leading-relaxed text-text/70">Coming soon.</p>
            </article>
        </div>
    </div>

    {{-- Psalm quote on phones only (tablets and desktop show it in the hero). Same text and config as the hero verse. --}}
    @if (filled(config('ministry.verse.text')))
        <figure class="mt-12 border-t border-text/10 pt-8 md:hidden">
            <blockquote>
                <p class="text-xl leading-relaxed text-text/80">&ldquo;{{ config('ministry.verse.text') }}&rdquo;</p>
            </blockquote>
            @if (filled(config('ministry.verse.reference')))
                <figcaption class="t-label mt-4 text-text/60">{{ config('ministry.verse.reference') }}</figcaption>
            @endif
        </figure>
    @endif

    {{-- Readouts as a meter bridge: a dB tick scale runs above each live count, across the full width. --}}
    @if (count($stats) > 0)
        <dl class="mt-16 grid gap-8 border-t border-text/10 pt-8 {{ match (count($stats)) { 3 => 'sm:grid-cols-3', 2 => 'sm:grid-cols-2', default => '' } }} lg:mt-24 lg:gap-8">
            @foreach ($stats as $stat)
                <div class="flex flex-col-reverse gap-3">
                    <dt class="t-label text-text/60">{{ $stat['label'] }}</dt>
                    <dd class="font-mono text-5xl font-medium tabular-nums tracking-tight lg:text-6xl" data-count="{{ $stat['value'] }}">
                        <span aria-hidden="true" data-count-value>{{ $stat['value'] }}</span>
                        <span class="sr-only">{{ $stat['value'] }}</span>
                    </dd>
                    <span aria-hidden="true" class="flex h-3 items-end justify-between">
                        @for ($tick = 0; $tick <= 20; $tick++)
                            <span class="w-px {{ $tick % 5 === 0 ? 'h-3 bg-text/50' : 'h-1.5 bg-text/25' }}"></span>
                        @endfor
                    </span>
                </div>
            @endforeach
        </dl>
    @endif
</x-section>
