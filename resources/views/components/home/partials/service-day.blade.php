@php
    // One upcoming day: mono date label, optional Today/Tomorrow badge, then one line per start time.
    $dateLabel = $day['date']->format('l, M j');
@endphp

<li class="border-t border-text/10 py-5 first:border-t-0 first:pt-0">
    <div class="flex items-center gap-3">
        <h3 class="t-label text-text/70">{{ $dateLabel }}</h3>
        @if ($badge)
            <span class="rounded-console border border-primary/40 px-2 py-0.5 font-mono text-[11px] font-medium uppercase tracking-[0.12em] text-primary-dark dark:text-primary">{{ $badge }}</span>
        @endif
    </div>

    <ul class="mt-3 space-y-2">
        @foreach ($day['items'] as $item)
            <li class="flex flex-wrap items-baseline gap-x-4 gap-y-1">
                <time datetime="{{ $item['starts_at']->format('c') }}" class="font-mono text-2xl font-medium tabular-nums tracking-tight">{{ $item['starts_at']->format('g:i A') }}</time>
                <span class="font-medium">{{ $item['service']->name }}</span>
                {{-- Compact icon button: the label lives in aria-label (and a tooltip), so rows stay quiet. --}}
                <a
                    href="{{ route('services.calendar', $item['service']) }}"
                    title="Add to calendar"
                    aria-label="Add {{ $item['service']->name }} {{ $dateLabel }} {{ $item['starts_at']->format('g:i A') }} to calendar"
                    class="ml-auto inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-console text-text/60 transition-colors duration-200 hover:bg-text/5 hover:text-primary-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-signal dark:hover:text-primary"
                >
                    <x-tabler-icon name="calendar" class="h-5 w-5" />
                </a>
            </li>
        @endforeach
    </ul>
</li>
