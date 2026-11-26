@props(['notification'])

@php
    $unread = is_null($notification->read_at);
    $data = $notification->data;
@endphp

<form method="POST" action="{{ route('notifications.read', $notification->id) }}">
    @csrf
    <button
        type="submit"
        class="flex min-h-11 w-full items-start gap-3 rounded-xl px-3 py-3 text-left transition-colors duration-200 hover:bg-text/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
    >
        <x-notification-icon :type="$data['type'] ?? null" />
        <span class="min-w-0 flex-1">
            <span class="block break-words text-sm text-text {{ $unread ? 'font-semibold' : 'font-normal text-text/80' }}">{{ $data['message'] ?? '' }}</span>
            <span class="mt-0.5 block text-xs text-text/70">{{ $notification->created_at->diffForHumans() }}</span>
        </span>
        @if ($unread)
            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full bg-accent" aria-hidden="true"></span>
            <span class="sr-only">{{ __('Unread') }}</span>
        @endif
    </button>
</form>
