@php
    // One name per service, with its start times as chips (same name at several times stays on one line).
    $groups = collect($day['items'])->groupBy(fn ($item) => $item['service']->name);
    $dateLabel = $day['date']->format('l, M j');
@endphp

<li class="group flex flex-col rounded-3xl border border-text/10 bg-background p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg motion-reduce:transform-none sm:p-6">
    <div class="flex items-start gap-3">
        {{-- The heading is the full date for screen readers. The visual block (weekday eyebrow + big "Nov 28") is
             aria-hidden and placed first with CSS order, so the badge can sit right after the heading in the markup. --}}
        <h3 class="sr-only">{{ $dateLabel }}</h3>

        @if ($badge)
            <span class="order-last ml-auto shrink-0 rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent-text ring-1 ring-inset ring-accent/30">{{ $badge }}</span>
        @endif

        <div aria-hidden="true" class="order-first min-w-0">
            <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-text/70">{{ $day['date']->format('l') }}</span>
            <span class="mt-1 block text-2xl font-extrabold leading-tight tracking-tight text-text">{{ $day['date']->format('M j') }}</span>
        </div>
    </div>

    <div class="mt-5 space-y-4">
        @foreach ($groups as $name => $items)
            <div>
                <p class="text-sm font-semibold text-text">{{ $name }}</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($items as $item)
                        <li class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border border-text/10 bg-surface px-3 py-1 text-sm text-text">
                            <svg class="h-3.5 w-3.5 shrink-0 text-primary-dark" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ $item['starts_at']->format('g:i A') }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</li>
