@props(['branch' => null, 'occurrences' => collect()])

@php
    $tz = config('app.timezone');
    // Immutable on purpose: addDays()/addDay() below must not move "today".
    $today = now($tz)->toImmutable()->startOfDay();
    $cutoff = $today->addDays(7);

    // One entry per calendar day: ['date' => CarbonImmutable, 'items' => occurrences that day].
    $days = collect($occurrences)
        ->groupBy(fn ($o) => $o['starts_at']->format('Y-m-d'))
        ->map(fn ($items) => ['date' => $items->first()['starts_at']->startOfDay(), 'items' => $items])
        ->values();

    $soon = $days->filter(fn ($d) => $d['date']->lessThan($cutoff))->values();
    $later = $days->filter(fn ($d) => $d['date']->greaterThanOrEqualTo($cutoff))->values();

    // Weekly summary derived from the data: one line per weekday + service name.
    $weekly = collect($occurrences)
        ->unique(fn ($o) => $o['service']->id)
        ->groupBy(fn ($o) => $o['service']->day_of_week.'|'.$o['service']->name)
        ->map(fn ($items) => [
            'day' => $items->first()['service']->day_of_week,
            'name' => $items->first()['service']->name,
            'times' => $items->sortBy(fn ($o) => $o['service']->start_time)
                ->map(fn ($o) => $o['starts_at']->format('g:i A'))->all(),
        ])
        ->sortBy('day')
        ->values();

    $dayName = fn (int $n) => \Carbon\CarbonImmutable::now($tz)->startOfWeek(\Carbon\CarbonInterface::SUNDAY)->addDays($n)->format('l');

    $badge = function ($date) use ($today) {
        if ($date->isSameDay($today)) {
            return 'Today';
        }

        return $date->isSameDay($today->addDay()) ? 'Tomorrow' : null;
    };
@endphp

<section id="services" aria-labelledby="services-title" class="bg-background py-16 sm:py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="services-title" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Upcoming Services</h2>
            @if ($branch)
                <p class="mt-4 text-base leading-relaxed text-text/70">At {{ $branch->name }}</p>
            @endif
        </div>

        @if ($days->isEmpty())
            <p class="mt-12 text-center text-base text-text/70">Service times will be posted soon.</p>
        @else
            <div x-data="{ showMore: false }" class="mt-12">
                <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($soon as $day)
                        @include('components.home.partials.service-day', ['day' => $day, 'badge' => $badge($day['date'])])
                    @endforeach
                </ul>

                @if ($later->isNotEmpty())
                    <ul id="more-service-dates" x-show="showMore" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($later as $day)
                            @include('components.home.partials.service-day', ['day' => $day, 'badge' => $badge($day['date'])])
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
                        <li>
                            @if (count($line['times']) > 1)
                                <span class="font-medium text-text">{{ $line['name'] }}:</span> {{ implode(', ', $line['times']) }}
                            @else
                                <span class="font-medium text-text">{{ $dayName($line['day']) }}:</span> {{ $line['name'] }}, {{ $line['times'][0] }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</section>
