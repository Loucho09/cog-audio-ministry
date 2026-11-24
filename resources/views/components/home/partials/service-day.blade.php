@php
    // One name per service, with its start times as chips (same name at several times stays on one line).
    $groups = collect($day['items'])->groupBy(fn ($item) => $item['service']->name);
@endphp

<li class="rounded-2xl border border-text/10 bg-background p-5 transition-shadow duration-200 hover:shadow-md">
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-base font-semibold text-text">{{ $day['date']->format('l, M j') }}</h3>
        @if ($badge)
            <span class="rounded-full bg-primary-dark px-2.5 py-0.5 text-xs font-medium text-surface">{{ $badge }}</span>
        @endif
    </div>
    <div class="mt-3 space-y-4">
        @foreach ($groups as $name => $items)
            <div>
                <p class="text-sm font-medium text-text">{{ $name }}</p>
                <ul class="mt-2 flex flex-wrap gap-2">
                    @foreach ($items as $item)
                        <li class="whitespace-nowrap rounded-full border border-text/10 bg-surface px-3 py-1 text-sm text-text">{{ $item['starts_at']->format('g:i A') }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</li>
