{{-- Small status pill for a duty. Colours are theme tokens only; text is AA on its tint in light and dark. --}}
@props(['status'])

@php
    $tone = match ($status) {
        \App\Enums\DutyStatus::Assigned => 'bg-primary/10 text-primary-dark',
        \App\Enums\DutyStatus::SwapRequested => 'bg-accent/10 text-accent-text',
        \App\Enums\DutyStatus::Declined => 'bg-text/5 text-text/70',
        default => 'bg-text/10 text-text',
    };
    $icon = match ($status) {
        \App\Enums\DutyStatus::Assigned => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        \App\Enums\DutyStatus::SwapRequested => 'M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5',
        \App\Enums\DutyStatus::Declined => 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        default => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    };
@endphp

<span {{ $attributes->class(['inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium', $tone]) }}>
    <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    {{ __($status->label()) }}
</span>
