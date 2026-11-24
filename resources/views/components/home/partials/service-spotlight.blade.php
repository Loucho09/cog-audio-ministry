@php
    // Next service day, shown large. The first start time is the "next start" box; any other times that day are listed below it.
    $next = collect($day['items'])->first();
    $later = collect($day['items'])->slice(1)->values();
    $dateLabel = $day['date']->format('l, M j');
    $directionsUrl = \App\Support\Directions::url();
@endphp

<article class="relative overflow-hidden rounded-3xl border border-text/15 bg-surface p-6 dark:bg-background shadow-sm dark:shadow-[0_0_48px_-12px] dark:shadow-primary/20 sm:p-8">
    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-accent"></span>

    <div class="flex items-start gap-3">
        {{-- Heading is the full date for screen readers; the visual block is aria-hidden and placed first with CSS order. --}}
        <h3 class="sr-only">{{ $dateLabel }}</h3>

        @if ($badge)
            <span class="order-last ml-auto shrink-0 rounded-full bg-accent/15 px-3 py-1 text-xs font-semibold text-accent-text ring-1 ring-inset ring-accent/30">{{ $badge }}</span>
        @endif

        <div aria-hidden="true" class="order-first min-w-0">
            <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-text/80">{{ $day['date']->format('l') }}</span>
            <time datetime="{{ $day['date']->format('Y-m-d') }}" class="mt-1 block text-3xl font-extrabold leading-tight tracking-tight text-text sm:text-4xl">{{ $day['date']->format('M j') }}</time>
        </div>
    </div>

    <div class="mt-6 rounded-2xl bg-primary/10 p-5 ring-1 ring-inset ring-primary/25">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-text/80">Next start</p>
        <p class="mt-1 text-4xl font-extrabold tracking-tight text-primary-dark">
            <time datetime="{{ $next['starts_at']->format('c') }}">{{ $next['starts_at']->format('g:i A') }}</time>
        </p>
        <p class="mt-1 text-base font-semibold text-text">{{ $next['service']->name }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
            <a href="{{ route('services.calendar', $next['service']) }}" class="inline-block text-sm font-semibold text-primary-dark underline underline-offset-4 hover:no-underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Add to calendar<span class="sr-only">: {{ $next['service']->name }} {{ $next['starts_at']->format('g:i A') }}</span></a>
            @if ($directionsUrl)
                <a href="{{ $directionsUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-dark underline underline-offset-4 hover:no-underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                    </svg>
                    Get directions<span class="sr-only"> (opens Google Maps)</span>
                </a>
            @endif
        </div>
    </div>

    @if ($later->isNotEmpty())
        <div class="mt-6 border-t border-text/15 pt-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-text/80">Later that day</p>
            <ul class="mt-3 flex flex-wrap gap-2">
                @foreach ($later as $item)
                    <li>
                        <time datetime="{{ $item['starts_at']->format('c') }}" class="inline-flex flex-wrap items-center gap-x-2 rounded-full border border-text/15 bg-surface px-3.5 py-1.5 text-sm text-text">
                            <span class="font-semibold">{{ $item['starts_at']->format('g:i A') }}</span>
                            <span class="text-text/80">{{ $item['service']->name }}</span>
                        </time>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</article>
