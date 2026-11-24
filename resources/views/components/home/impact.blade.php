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
    // Stats sit in a half-width column on lg, so three cards need a smaller number and tighter padding.
    $lgCard = count($stats) === 3 ? 'lg:px-4 lg:py-8' : 'lg:px-8 lg:py-10';
    $lgNumber = count($stats) === 3 ? 'lg:text-6xl' : 'lg:text-7xl';
@endphp

@if (count($stats) > 0)
    <section id="impact" aria-labelledby="impact-title" class="relative isolate overflow-hidden bg-background py-14 sm:py-16">
        {{-- Thin primary-to-accent light bar on the top edge, and a subtle backdrop. Decorative. --}}
        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r from-primary to-accent"></span>
        <x-site.section-backdrop variant="vignette" />

        <div class="relative mx-auto max-w-7xl px-5 text-center sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:items-center lg:gap-16">
                <x-site.section-header
                    id="impact-title"
                    eyebrow="Our impact"
                    title="Every sermon, every song, heard clearly."
                    align="split"
                />

                <dl class="mx-auto mt-10 grid max-w-3xl gap-4 text-center sm:gap-6 lg:mt-0 lg:max-w-none lg:gap-6 {{ $gridCols }}">
                @foreach ($stats as $stat)
                    {{-- Stat card: white in light mode, with a thin stage-colour bar on top. --}}
                    <div class="relative flex flex-col-reverse items-center gap-1 overflow-hidden rounded-3xl border border-text/15 bg-surface px-6 py-7 shadow-sm dark:bg-surface/60 {{ $lgCard }}">
                        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary to-accent"></span>
                        <dt class="text-sm font-semibold uppercase tracking-wider text-text/80">{{ $stat['label'] }}</dt>
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
                            class="bg-gradient-to-r from-primary-dark to-accent bg-clip-text pb-1 text-5xl font-extrabold tabular-nums text-transparent sm:text-6xl {{ $lgNumber }} forced-colors:bg-none forced-colors:text-[CanvasText]"
                        >
                            <span x-text="shown" aria-hidden="true">{{ $stat['value'] }}</span>
                            <span class="sr-only">{{ $stat['value'] }}</span>
                        </dd>
                    </div>
                @endforeach
                </dl>
            </div>
        </div>
    </section>
@endif
