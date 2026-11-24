@props(['branch' => null, 'schedule' => ['soon' => collect(), 'later' => collect(), 'weekly' => collect()]])

@php
    $soon = $schedule['soon'];
    $later = $schedule['later'];
    $weekly = $schedule['weekly'];
@endphp

<section id="services" aria-labelledby="services-title" class="bg-background py-16 sm:py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="services-title" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Upcoming Services</h2>
            @if ($branch)
                <p class="mt-4 text-base leading-relaxed text-text/70">At {{ $branch->name }}</p>
            @endif
        </div>

        @if ($soon->isEmpty() && $later->isEmpty())
            <p class="mt-12 text-center text-base text-text/70">Service times will be posted soon.</p>
        @else
            <div x-data="{ showMore: false }" class="mt-12">
                <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($soon as $day)
                        @include('components.home.partials.service-day', ['day' => $day, 'badge' => $day['badge']])
                    @endforeach
                </ul>

                @if ($later->isNotEmpty())
                    <ul id="more-service-dates" x-show="showMore" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($later as $day)
                            @include('components.home.partials.service-day', ['day' => $day, 'badge' => $day['badge']])
                        @endforeach
                    </ul>

                    <div class="mt-8 text-center">
                        <x-site.button variant="secondary" x-on:click="showMore = !showMore" x-bind:aria-expanded="showMore.toString()" aria-expanded="false" aria-controls="more-service-dates">
                            <span x-text="showMore ? 'Show fewer dates' : 'Show more dates'">Show more dates</span>
                        </x-site.button>
                    </div>
                @endif
            </div>

            <div class="mx-auto mt-14 max-w-2xl rounded-2xl border border-text/10 bg-surface p-6 sm:p-8">
                <h3 class="text-lg font-semibold text-text">Weekly schedule</h3>
                <ul class="mt-4 space-y-2 text-base text-text/70">
                    @foreach ($weekly as $line)
                        <li><span class="font-medium text-text">{{ $line['day'] }}:</span> {{ $line['name'] }}, {{ implode(', ', $line['times']) }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
