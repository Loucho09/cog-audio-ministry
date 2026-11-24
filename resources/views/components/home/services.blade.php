@props(['branch' => null, 'schedule' => ['soon' => collect(), 'later' => collect(), 'weekly' => collect()]])

@php
    // `later` is intentionally not rendered: the homepage shows only the next 7 days plus the weekly list.
    $soon = $schedule['soon'];
    $weekly = $schedule['weekly'];
    $spotlight = $soon->first();
    $upcoming = $soon->slice(1)->values();
    $todayName = now()->format('l');
@endphp

<section id="services" aria-labelledby="services-title" class="relative isolate overflow-hidden bg-surface py-16 sm:py-20 lg:py-24">
    <x-site.section-backdrop variant="cone" fade-from="background" />
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <x-site.section-header
            id="services-title"
            eyebrow="Services"
            title="Upcoming Services"
            :lead="$branch ? 'At '.$branch->name : null"
        />

        @if ($soon->isEmpty() && $weekly->isEmpty())
            <p class="mt-10 text-center text-base text-text/80 sm:mt-12">Service times will be posted soon.</p>
        @else
            <div class="mt-10 sm:mt-12 lg:grid lg:grid-cols-12 lg:items-stretch lg:gap-8">
                @if ($spotlight)
                    <div class="mx-auto max-w-3xl {{ $upcoming->isNotEmpty() ? 'lg:col-span-7' : 'lg:col-span-12' }} lg:mx-0 lg:flex lg:max-w-none lg:flex-col lg:[&>*]:flex-1">
                        @include('components.home.partials.service-spotlight', ['day' => $spotlight, 'badge' => $spotlight['badge']])
                    </div>
                @endif

                @if ($upcoming->isNotEmpty())
                    {{-- Flex + centred on mobile. On lg, a stacked list in the right column beside the featured service. --}}
                    <ul class="mt-6 flex flex-wrap justify-center gap-4 sm:mt-8 [&>li]:w-full {{ $spotlight ? 'lg:col-span-5' : 'lg:col-span-12' }} lg:mt-0 lg:flex-col lg:flex-nowrap lg:justify-start lg:[&>li]:flex-1">
                        @foreach ($upcoming as $day)
                            @include('components.home.partials.service-day', ['day' => $day, 'badge' => $day['badge']])
                        @endforeach
                    </ul>
                @endif
            </div>

            @if ($weekly->isNotEmpty())
                <div x-data="{ openWeekly: false }" class="mt-10 sm:mt-12">
                    <div class="text-center">
                        <x-site.button
                            variant="secondary"
                            x-on:click="openWeekly = !openWeekly"
                            x-bind:aria-expanded="openWeekly.toString()"
                            aria-expanded="false"
                            aria-controls="weekly-schedule"
                        >
                            <span x-text="openWeekly ? 'Hide weekly schedule' : 'See weekly schedule'">See weekly schedule</span>
                        </x-site.button>
                    </div>

                    <div id="weekly-schedule" x-show="openWeekly" x-cloak class="mx-auto mt-6 max-w-3xl rounded-3xl border border-text/15 bg-surface p-5 dark:bg-background shadow-sm sm:p-6">
                        <h3 class="text-lg font-bold text-text">Weekly schedule</h3>
                        <p class="mt-1 text-sm text-text/80">Tap a time to add it to your calendar.</p>
                        <ul class="mt-4 space-y-1">
                            @foreach ($weekly as $line)
                                @php
                                    $isToday = $line['day'] === $todayName;
                                @endphp
                                <li class="rounded-2xl px-3 py-3 {{ $isToday ? 'bg-primary/10 ring-1 ring-inset ring-primary/30' : '' }}">
                                    <div class="flex flex-col gap-2 sm:grid sm:grid-cols-[7rem_minmax(0,1fr)_auto] sm:items-center sm:gap-4">
                                        <p class="text-sm font-bold text-text">{{ $line['day'] }}@if ($isToday)<span class="sr-only"> (today)</span>@endif</p>
                                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                                            <span class="text-sm text-text">{{ $line['name'] }}</span>
                                            @if (isset($line['note']))
                                                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary-dark ring-1 ring-inset ring-primary/25">{{ $line['note'] }}</span>
                                            @endif
                                        </div>
                                        <ul class="flex flex-wrap items-center gap-2">
                                            @foreach ($line['times'] as $time)
                                                @php
                                                    $timeNote = collect($line['notes'] ?? [])->firstWhere('time', $time);
                                                @endphp
                                                <li class="inline-flex flex-wrap items-center gap-1.5">
                                                    <a href="{{ $line['calendar_urls'][$loop->index] }}" class="inline-flex min-h-9 items-center gap-1.5 whitespace-nowrap rounded-full border border-text/15 bg-surface px-3 py-1.5 text-sm text-text hover:border-primary/40 hover:bg-primary/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                                        {{ $time }}
                                                        <svg aria-hidden="true" class="h-3.5 w-3.5 text-primary-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 5h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1z"/></svg>
                                                        <span class="sr-only">: add {{ $line['name'] }} to calendar</span>
                                                    </a>
                                                    @if ($timeNote)
                                                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary-dark ring-1 ring-inset ring-primary/25">{{ $timeNote['note'] }}</span>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        @endif
    </div>
</section>
