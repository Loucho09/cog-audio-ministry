{{-- Bell + dropdown. Data comes from the view composer in AppServiceProvider. --}}
<div x-data="{ open: false }" @keydown.escape.window="if (open) { open = false; $refs.bell.focus() }" @click.outside="open = false" class="sm:relative">
    <button
        type="button"
        x-ref="bell"
        @click="open = !open"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        aria-controls="notification-panel"
        aria-label="Notifications, {{ $unreadCount }} unread"
        class="relative inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-text transition-colors duration-200 hover:bg-text/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
    >
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute right-0.5 top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1 text-[0.6875rem] font-bold leading-none text-white" aria-hidden="true">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div
        id="notification-panel"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="absolute inset-x-0 top-full z-50 border-b border-text/15 bg-surface shadow-lg sm:inset-x-auto sm:right-0 sm:mt-2 sm:w-96 sm:rounded-2xl sm:border"
    >
        <div class="flex items-center justify-between gap-3 px-4 pb-1 pt-3">
            <h2 class="text-sm font-semibold text-text">{{ __('Notifications') }}</h2>
            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="min-h-11 rounded-md px-2 text-sm font-medium text-primary-dark underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">{{ __('Mark all as read') }}</button>
                </form>
            @endif
        </div>

        <div class="max-h-[60vh] overflow-y-auto px-1 pb-1">
            @forelse ($latest as $notification)
                <x-notification-item :notification="$notification" />
            @empty
                <p class="px-4 py-6 text-center text-sm text-text/70">{{ __("You're all caught up.") }}</p>
            @endforelse
        </div>

        <div class="border-t border-text/15 px-2 py-1 text-center">
            <a href="{{ route('notifications.index') }}" class="inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-primary-dark underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">{{ __('See all') }}</a>
        </div>
    </div>
</div>
