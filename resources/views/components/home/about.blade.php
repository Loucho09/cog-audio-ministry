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

    <div class="mt-12 grid gap-6 lg:mt-16 lg:grid-cols-12 lg:gap-8">
        <p class="t-lead text-text/80 lg:col-span-5">
            The Audio Ministry cares for the sound, stage, and livestream of our services in Dasmariñas, so worship can be heard and shared by everyone in the room and at home.
        </p>

        <div class="grid gap-6 border-t border-text/10 pt-6 sm:grid-cols-2 lg:col-span-6 lg:col-start-7 lg:gap-8">
            <article aria-labelledby="vision-title">
                <p aria-hidden="true" class="t-label text-text/60">V — 01</p>
                <h3 id="vision-title" class="mt-3 text-xl font-semibold tracking-tight">Our Vision</h3>
                {{-- TODO: vision/mission from the Audio Head --}}
                <p class="mt-3 leading-relaxed text-text/70">Coming soon.</p>
            </article>
            <article aria-labelledby="mission-title">
                <p aria-hidden="true" class="t-label text-text/60">M — 02</p>
                <h3 id="mission-title" class="mt-3 text-xl font-semibold tracking-tight">Our Mission</h3>
                {{-- TODO: vision/mission from the Audio Head --}}
                <p class="mt-3 leading-relaxed text-text/70">Coming soon.</p>
            </article>
        </div>
    </div>

    @if (count($stats) > 0)
        <dl class="mt-16 grid gap-6 border-t border-text/10 pt-8 {{ match (count($stats)) { 3 => 'sm:grid-cols-3', 2 => 'sm:grid-cols-2', default => '' } }} lg:mt-20 lg:gap-8">
            @foreach ($stats as $stat)
                <div class="flex flex-col-reverse gap-2 sm:border-l sm:border-text/10 sm:pl-6 sm:first:border-l-0 sm:first:pl-0">
                    <dt class="t-label text-text/60">{{ $stat['label'] }}</dt>
                    <dd class="font-mono text-5xl font-medium tabular-nums tracking-tight lg:text-6xl" data-count="{{ $stat['value'] }}">
                        <span aria-hidden="true" data-count-value>{{ $stat['value'] }}</span>
                        <span class="sr-only">{{ $stat['value'] }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
    @endif
</x-section>
