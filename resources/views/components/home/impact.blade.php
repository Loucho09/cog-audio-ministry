{{-- Impact strip: a few honest numbers. The years stat only shows when the owner sets it in config. --}}
@props(['servicesPerWeek' => 0])

@php
    $stats = [
        ['value' => count(config('ministry.churches', [])), 'label' => 'Churches served'],
        ['value' => (int) $servicesPerWeek, 'label' => 'Services every week'],
        ['value' => (int) config('ministry.years_serving'), 'label' => 'Years serving'],
    ];
    $stats = array_values(array_filter($stats, fn ($stat) => $stat['value'] > 0));
@endphp

@if (count($stats) > 0)
    <section id="impact" aria-labelledby="impact-title" class="relative isolate overflow-hidden bg-surface py-14 sm:py-16">
        <div class="mx-auto max-w-6xl px-5 text-center sm:px-6 lg:px-8">
            <h2 id="impact-title" class="text-xl font-extrabold leading-snug tracking-tight text-text [text-wrap:balance] sm:text-2xl">
                Every sermon, every song, heard clearly.
            </h2>

            <dl class="mx-auto mt-10 grid max-w-3xl gap-8 {{ count($stats) >= 3 ? "sm:grid-cols-3" : (count($stats) === 2 ? "sm:grid-cols-2" : "") }}">
                @foreach ($stats as $stat)
                    <div class="flex flex-col-reverse items-center gap-1">
                        <dt class="text-sm font-semibold uppercase tracking-wider text-text/70">{{ $stat['label'] }}</dt>
                        <dd
                            x-data="{ shown: 0, target: {{ $stat['value'] }} }"
                            x-init="
                                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { shown = target; return; }
                                const step = () => { shown < target ? (shown++, setTimeout(step, 120)) : null };
                                step();
                            "
                            class="text-5xl font-extrabold tabular-nums text-primary"
                        >
                            <span x-text="shown" aria-hidden="true">{{ $stat['value'] }}</span>
                            <span class="sr-only">{{ $stat['value'] }}</span>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>
@endif
