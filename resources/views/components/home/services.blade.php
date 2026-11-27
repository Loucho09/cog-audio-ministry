@props(['branch' => null, 'schedule' => ['soon' => collect(), 'later' => collect(), 'weekly' => collect()]])

@php
    // `later` is intentionally not rendered: the homepage shows only the next 7 days plus the weekly timetable.
    $soon = $schedule['soon'];
    $weekly = $schedule['weekly'];
    $todayName = now()->format('l');
    $directionsUrl = \App\Support\Directions::url();
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // One slot per start time. data-day is 0 (Sunday) to 6, data-time is 24h HH:MM, both read by next-service.js.
    $slots = $weekly->flatMap(function (array $line) use ($dayNames) {
        $day = array_search($line['day'], $dayNames, true);

        return collect($line['times'])->map(fn (string $time, int $i) => [
            'day' => $line['day'],
            'dow' => $day,
            'time' => $time,
            'time24' => \Carbon\CarbonImmutable::parse($time)->format('H:i'),
            'name' => $line['name'],
            'url' => $line['calendar_urls'][$i] ?? null,
            'note' => $line['note'] ?? collect($line['notes'] ?? [])->firstWhere('time', $time)['note'] ?? null,
        ]);
    })->sortBy(fn (array $s) => sprintf('%d-%s', $s['dow'], $s['time24']))->values();

    $weekdaySlots = $slots->filter(fn (array $s) => $s['dow'] !== 0)->values();
    $sundaySlots = $slots->filter(fn (array $s) => $s['dow'] === 0)->values();
@endphp

<x-section id="services" tone="surface">
    <div data-fx="fader-heading">
        <x-section-heading
            id="services"
            channel="03"
            eyebrow="Services"
            title="When we gather."
            :description="$branch ? 'At '.$branch->name : null"
            align="split"
        />
    </div>

    @if ($soon->isEmpty() && $weekly->isEmpty())
        <p class="mt-12 max-w-xl text-text/70 lg:mt-16">Service times will be posted soon.</p>
    @else
        <div class="mt-12 grid gap-10 lg:mt-16 lg:grid-cols-12 lg:gap-8">
            {{-- Coming up: server-rendered dates with Today / Tomorrow badges. Works without JS. --}}
            @if ($soon->isNotEmpty())
                <div class="lg:col-span-4">
                    <p class="t-label text-text/60">Coming up</p>
                    <ul class="mt-6">
                        @foreach ($soon as $day)
                            @include('components.home.partials.service-day', ['day' => $day, 'badge' => $day['badge']])
                        @endforeach
                    </ul>
                    @if ($directionsUrl)
                        <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer" class="link-fader mt-6 inline-flex min-h-11 items-center gap-2 text-sm text-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-signal dark:text-primary">
                            <x-tabler-icon name="map-pin" class="h-4 w-4" />
                            Get directions<span class="sr-only"> (opens Google Maps)</span>
                        </a>
                    @endif
                </div>
            @endif

            @if ($slots->isNotEmpty())
                <div class="{{ $soon->isNotEmpty() ? 'lg:col-span-8' : 'lg:col-span-12' }}">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="t-label text-text/60">Weekly schedule</h3>
                        <p class="t-label text-text/40">Times in Manila</p>
                    </div>

                    @if ($weekdaySlots->isNotEmpty())
                        <ul class="mt-6 divide-y divide-text/10 border-y border-text/10" data-fx="meters">
                            @foreach ($weekdaySlots as $slot)
                                @php $isToday = $slot['day'] === $todayName; @endphp
                                <li
                                    data-day="{{ $slot['dow'] }}"
                                    data-time="{{ $slot['time24'] }}"
                                    data-name="{{ $slot['name'] }}"
                                    @class(['relative grid grid-cols-[auto_1fr] items-baseline gap-x-6 gap-y-1 py-5 data-[state]:bg-primary/5 sm:grid-cols-[7rem_8rem_1fr_auto]', 'bg-primary/5' => $isToday])
                                >
                                    <p class="t-label text-text/70">{{ $slot['day'] }}@if ($isToday)<span class="sr-only"> (today)</span>@endif</p>
                                    <p class="font-mono text-2xl font-medium tabular-nums tracking-tight">{{ $slot['time'] }}</p>
                                    <div class="col-span-2 flex flex-wrap items-center gap-x-3 gap-y-1 sm:col-span-1">
                                        <span class="font-medium">{{ $slot['name'] }}</span>
                                        @if ($slot['note'])
                                            <span class="font-mono text-xs uppercase tracking-[0.12em] text-text/60">{{ $slot['note'] }}</span>
                                        @endif
                                        <span class="js-led items-center gap-2 font-mono text-[11px] font-medium uppercase tracking-[0.12em] text-primary-dark dark:text-signal"><span aria-hidden="true" class="led-dot"></span><span class="js-led-label"></span></span>
                                    </div>
                                    @if ($slot['url'])
                                        <a href="{{ $slot['url'] }}" class="link-fader col-span-2 text-sm text-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-signal sm:col-span-1 dark:text-primary">Add to calendar<span class="sr-only">: {{ $slot['name'] }} {{ $slot['time'] }}</span></a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($sundaySlots->isNotEmpty())
                        <div class="mt-8">
                            <p class="t-label text-text/70">Sunday @if ($todayName === 'Sunday')<span class="sr-only"> (today)</span>@endif</p>
                            <ul class="mt-4 flex flex-wrap gap-4" data-fx="strips">
                                @foreach ($sundaySlots as $slot)
                                    <li
                                        data-day="0"
                                        data-time="{{ $slot['time24'] }}"
                                        data-name="{{ $slot['name'] }}"
                                        class="flex min-w-[9rem] flex-1 basis-40 flex-col gap-1 rounded-console border border-text/10 bg-surface p-4 data-[state]:border-primary/40 data-[state]:bg-primary/5 lg:p-5"
                                    >
                                        <p class="font-mono text-xl font-medium tabular-nums tracking-tight sm:text-2xl">{{ $slot['time'] }}</p>
                                        <p class="text-sm font-medium">{{ $slot['name'] }}</p>
                                        @if ($slot['note'])
                                            <p class="font-mono text-xs uppercase tracking-[0.12em] text-text/60">{{ $slot['note'] }}</p>
                                        @endif
                                        <span class="js-led items-center gap-2 font-mono text-[11px] font-medium uppercase tracking-[0.12em] text-primary-dark dark:text-signal"><span aria-hidden="true" class="led-dot"></span><span class="js-led-label"></span></span>
                                        @if ($slot['url'])
                                            <a href="{{ $slot['url'] }}" class="link-fader mt-1 self-start text-sm text-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-signal dark:text-primary">Add to calendar<span class="sr-only">: {{ $slot['name'] }} {{ $slot['time'] }}</span></a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</x-section>
