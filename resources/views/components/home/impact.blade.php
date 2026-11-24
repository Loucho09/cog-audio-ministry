{{-- Impact strip: a few honest numbers. The years stat only shows when the owner sets it in config. --}}
@props(['servicesPerWeek' => 0])

@php
    $stats = [
        ['value' => count(config('ministry.churches', [])), 'label' => 'Churches served'],
        ['value' => (int) $servicesPerWeek, 'label' => 'Services every week'],
        ['value' => (int) config('ministry.years_serving'), 'label' => 'Years serving'],
    ];
    $stats = array_values(array_filter($stats, fn ($stat) => $stat['value'] > 0));
    $gridCols = match (count($stats)) {
        3 => 'sm:grid-cols-3',
        2 => 'sm:grid-cols-2',
        default => '',
    };
@endphp

@if (count($stats) > 0)
    <section id="impact" aria-labelledby="impact-title" class="relative isolate overflow-hidden bg-background py-14 sm:py-16">
        {{-- Thin primary-to-accent light bar on the top edge, and a subtle backdrop. Decorative. --}}
        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-primary to-accent"></span>
        <x-site.section-backdrop variant="vignette" />

        <div class="relative mx-auto max-w-6xl px-5 text-center sm:px-6 lg:px-8">
            <x-site.section-header
                id="impact-title"
                eyebrow="Our impact"
                title="Every sermon, every song, heard clearly."
            />

            <dl class="mx-auto mt-10 grid max-w-3xl gap-8 {{ $gridCols }}">
                @foreach ($stats as $stat)
                    <div class="flex flex-col-reverse items-center gap-1">
                        <dt class="text-sm font-semibold uppercase tracking-wider text-text/70">{{ $stat['label'] }}</dt>
                        <dd
                            x-data="{ shown: 0, target: {{ $stat['value'] }} }"
                            x-init="
                                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { shown = target; return; }
                                const duration = 1000;
                                const start = performance.now();
                                const tick = (now) => {
                                    const progress = Math.min((now - start) / duration, 1);
                                    shown = Math.round(target * progress);
                                    if (progress < 1) requestAnimationFrame(tick);
                                };
                                requestAnimationFrame(tick);
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
