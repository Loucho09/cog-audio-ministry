<x-app-layout :title="__('Notifications')">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ __('Notifications') }}</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-md border border-text/15 bg-surface px-4 text-sm font-medium text-text transition-colors duration-200 hover:bg-text/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">{{ __('Mark all as read') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <section aria-label="{{ __('Notifications') }}" class="rounded-2xl border border-text/15 bg-surface p-2 shadow-sm sm:p-3">
        @forelse ($notifications as $notification)
            <x-notification-item :notification="$notification" />
        @empty
            <p class="px-4 py-10 text-center text-sm text-text/70">{{ __("You're all caught up.") }}</p>
        @endforelse
    </section>

    @if ($notifications->hasPages())
        <div class="mt-6">{{ $notifications->links() }}</div>
    @endif
</x-app-layout>
