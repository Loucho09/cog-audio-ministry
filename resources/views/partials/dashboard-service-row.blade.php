<li class="flex flex-col py-2 sm:flex-row sm:items-baseline sm:justify-between sm:gap-x-4">
    <span class="text-text">
        {{ $item['service']->name }}
        <span class="text-text/70">&middot; {{ $item['branch']->name }}</span>
        @if ($item['is_own'])
            <span class="ms-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary-dark">{{ __('Your branch') }}</span>
        @endif
    </span>
    <time datetime="{{ $item['starts_at']->toIso8601String() }}" class="text-text/70">
        {{ $item['starts_at']->format('D, M j') }} &middot; {{ $item['starts_at']->format('g:i A') }}
    </time>
</li>
