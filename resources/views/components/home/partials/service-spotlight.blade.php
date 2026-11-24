@php
    // Next service day, shown large. The first start time is the "next start" box; any other times that day are listed below it.
    $next = collect($day['items'])->first();
    $later = collect($day['items'])->slice(1)->values();
    $dateLabel = $day['date']->format('l, M j');
@endphp

<article class="relative overflow-hidden rounded-3xl border border-text/10 bg-background p-6 shadow-sm dark:shadow-[0_0_48px_-12px] dark:shadow-primary/20 sm:p-8">
    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-accent"></span>

    <div class="flex items-start gap-3">
        {{-- Heading is the full date for screen readers; the visual block is aria-hidden and placed first with CSS order. --}}
        <h3 class="sr-only">{{ $dateLabel }}</h3>

        @if ($badge)
            <span class="order-last ml-auto shrink-0 rounded-full bg-accent/15 px-3 py-1 text-xs font-semibold text-accent-text ring-1 ring-inset ring-accent/30">{{ $badge }}</span>
        @endif

        <div aria-hidden="true" class="order-first min-w-0">
            <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-text/70">{{ $day['date']->format('l') }}</span>
            <time datetime="{{ $day['date']->format('Y-m-d') }}" class="mt-1 block text-3xl font-extrabold leading-tight tracking-tight text-text sm:text-4xl">{{ $day['date']->format('M j') }}</time>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-primary/10 p-5 ring-1 ring-inset ring-primary/25">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-text/70">Next start</p>
        <p class="mt-1 text-4xl font-extrabold tracking-tight text-primary-dark">
            <time datetime="{{ $next['starts_at']->format('c') }}">{{ $next['starts_at']->format('g:i A') }}</time>
        </p>
        <p class="mt-1 text-base font-semibold text-text">{{ $next['service']->name }}</p>
    </div>

    @if ($later->isNotEmpty())
        <div class="mt-6 border-t border-text/10 pt-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-text/70">Later that day</p>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($later as $item)
                    <li>
                        <time datetime="{{ $item['starts_at']->format('c') }}" class="inline-flex flex-wrap items-center gap-x-2 rounded-full border border-text/10 bg-surface px-3.5 py-1.5 text-sm text-text">
                            <span class="font-semibold">{{ $item['starts_at']->format('g:i A') }}</span>
                            <span class="text-text/70">{{ $item['service']->name }}</span>
                        </time>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</article>
