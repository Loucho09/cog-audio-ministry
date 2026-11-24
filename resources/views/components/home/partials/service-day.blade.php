@php
    // Compact card for one upcoming day. One name per service, with its start times as small chips.
    $groups = collect($day['items'])->groupBy(fn ($item) => $item['service']->name);
    $dateLabel = $day['date']->format('l, M j');
@endphp

<li class="group flex flex-col gap-3 rounded-3xl border border-text/10 bg-background p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-lg motion-reduce:transform-none dark:hover:shadow-[0_0_40px_-8px] dark:hover:shadow-primary/20 sm:p-5">
    <div class="flex items-start gap-3">
        {{-- Heading is the full date for screen readers; the visual block is aria-hidden and placed first with CSS order. --}}
        <h3 class="sr-only">{{ $dateLabel }}</h3>

        @if ($badge)
            <span class="order-last ml-auto shrink-0 rounded-full bg-accent/15 px-2.5 py-1 text-xs font-semibold text-accent-text ring-1 ring-inset ring-accent/30">{{ $badge }}</span>
        @endif

        <div aria-hidden="true" class="order-first min-w-0">
            <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-text/70">{{ $day['date']->format('l') }}</span>
            <time datetime="{{ $day['date']->format('Y-m-d') }}" class="mt-0.5 block text-xl font-extrabold leading-tight tracking-tight text-text">{{ $day['date']->format('M j') }}</time>
        </div>
    </div>

    <div class="space-y-3">
        @foreach ($groups as $name => $items)
            <div>
                <p class="text-sm font-semibold text-text">{{ $name }}</p>
                <ul class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach ($items as $item)
                        <li>
                            <time datetime="{{ $item['starts_at']->format('c') }}" class="inline-flex whitespace-nowrap rounded-full border border-text/10 bg-surface px-2.5 py-0.5 text-xs text-text">{{ $item['starts_at']->format('g:i A') }}</time>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</li>
