<li class="rounded-2xl border border-text/10 bg-surface p-5">
    <div class="flex items-center justify-between gap-2">
        <h3 class="text-base font-semibold text-text">{{ $day['date']->format('l, M j') }}</h3>
        @if ($badge)
            <span class="rounded-full bg-primary-dark px-2.5 py-0.5 text-xs font-medium text-surface">{{ $badge }}</span>
        @endif
    </div>
    <ul class="mt-3 space-y-2">
        @foreach ($day['items'] as $item)
            <li class="rounded-lg bg-background px-3 py-2 text-sm text-text">
                {{ $item['starts_at']->format('g:i A') }} &middot; {{ $item['service']->name }}
            </li>
        @endforeach
    </ul>
</li>
