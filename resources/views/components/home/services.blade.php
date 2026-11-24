@props(['branch' => null, 'schedule' => ['soon' => collect(), 'later' => collect(), 'weekly' => collect()]])

@php
    $soon = $schedule['soon'];
    $later = $schedule['later'];
    $weekly = $schedule['weekly'];
@endphp

<section id="services" aria-labelledby="services-title" class="bg-surface py-16 sm:py-20">
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
                <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($soon as $day)
                        @include('components.home.partials.service-day', ['day' => $day, 'badge' => $day['badge']])
                    @endforeach
                </ul>

                @if ($later->isNotEmpty())
                    <ul id="more-service-dates" x-show="showMore" x-cloak class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($later as $day)
                            @include('components.home.partials.service-day', ['day' => $day, 'badge' => $day['badge']])
                        @endforeach
                    </ul>

                    <div class="mt-8 text-center">
                        <x-site.button variant="secondary" x-on:click="showMore = !showMore" x-bind:aria-expanded="showMore.toString()" aria-expanded="false" aria-controls="more-service-dates" class="min-h-11">
                            <span x-text="showMore ? 'Show fewer dates' : 'Show more dates'">Show more dates</span>
                        </x-site.button>
                    </div>
                @endif
            </div>

            <div class="mx-auto mt-14 max-w-2xl rounded-2xl border border-text/10 bg-background p-6 sm:p-8">
                <h3 class="text-lg font-semibold text-text">Weekly schedule</h3>
                <dl class="mt-4 divide-y divide-text/10">
                    @foreach ($weekly as $line)
                        <div class="grid grid-cols-[6.5rem_1fr] gap-x-4 py-3 first:pt-0 last:pb-0 sm:grid-cols-[8rem_1fr]">
                            <dt class="whitespace-nowrap text-sm font-medium text-text">{{ $line['day'] }}:</dt>
                            <dd class="min-w-0">
                                <p class="text-sm text-text">{{ $line['name'] }}</p>
                                <ul class="mt-2 flex flex-wrap gap-2">
                                    @foreach ($line['times'] as $time)
                                        <li class="whitespace-nowrap rounded-full border border-text/10 bg-surface px-3 py-1 text-sm text-text">{{ $time }}</li>
                                    @endforeach
                                </ul>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @endif
    </div>
</section>
