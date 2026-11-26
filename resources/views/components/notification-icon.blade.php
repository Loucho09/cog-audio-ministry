@props(['type' => null])

@php
    $isSwap = str_starts_with((string) $type, 'swap_');
    $isReminder = str_starts_with((string) $type, 'duty_reminder');
    $tone = match ($type) {
        'swap_rejected', 'duty_reminder_soon' => 'bg-accent/10 text-accent-text',
        default => 'bg-primary/10 text-primary-dark',
    };
@endphp

<span {{ $attributes->class(['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', $tone]) }} aria-hidden="true">
    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        @if ($isSwap)
            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
        @elseif ($isReminder)
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        @else
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        @endif
    </svg>
</span>
