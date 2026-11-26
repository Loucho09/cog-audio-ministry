{{-- One duty with its status pill and the actions allowed for its status. --}}
@props(['duty'])

@php
    $event = $duty->event;
    $open = ! $event->hasStarted();
@endphp

<div>
    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
        <p class="min-w-0 break-words font-medium text-text">{{ $event->name }}</p>
        <x-duty-status-pill :status="$duty->status" />
    </div>
    <p class="mt-1 text-sm text-text/70">
        <time datetime="{{ $event->starts_at->toIso8601String() }}">{{ $event->starts_at->timezone(config('app.timezone'))->format('D, M j · g:i A') }}</time>
    </p>
    <p class="mt-0.5 text-sm text-text/70">{{ $event->branch->name }} &middot; {{ $duty->dutyRole->name }}</p>

    @if ($duty->status === \App\Enums\DutyStatus::Assigned && $open)
        <div class="mt-3 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('duties.accept', $duty) }}" data-submit-guard>
                @csrf
                <x-primary-button aria-label="{{ __('Accept :event duty', ['event' => $event->name]) }}">{{ __('Accept') }}</x-primary-button>
            </form>
            <form method="POST" action="{{ route('duties.decline', $duty) }}" data-submit-guard onsubmit="return confirm(@js(__("Decline this duty? You won't be able to take it back.")))">
                @csrf
                <x-secondary-button type="submit" class="!border-text/20 !text-text/80" aria-label="{{ __('Decline :event duty', ['event' => $event->name]) }}">{{ __('Decline') }}</x-secondary-button>
            </form>
        </div>
    @endif

    @if ($duty->status === \App\Enums\DutyStatus::Accepted && $open)
        <form method="POST" action="{{ route('swaps.store', $duty) }}" data-submit-guard class="mt-3" onsubmit="return confirm(@js(__('Offer this duty to another member? Your admin will confirm the swap.')))">
            @csrf
            <x-secondary-button type="submit" aria-label="{{ __('Offer swap for :event', ['event' => $event->name]) }}">{{ __('Offer swap') }}</x-secondary-button>
        </form>
    @endif

    @if ($duty->status === \App\Enums\DutyStatus::SwapRequested && ($swap = $duty->swaps->sortByDesc('id')->first()))
        <p class="mt-2 text-sm text-text/70">
            @if ($swap->claimedBy)
                {{ __('Claimed by :name, waiting for admin.', ['name' => $swap->claimedBy->name]) }}
            @else
                {{ __('Waiting for someone to take this duty.') }}
            @endif
        </p>
        @if ((int) $swap->offered_by === (int) auth()->id())
            <form method="POST" action="{{ route('swaps.cancel', $swap) }}" data-submit-guard class="mt-3" onsubmit="return confirm(@js(__('Cancel this swap and keep the duty?')))">
                @csrf
                <x-secondary-button type="submit" aria-label="{{ __('Cancel swap for :event', ['event' => $event->name]) }}">{{ __('Cancel swap') }}</x-secondary-button>
            </form>
        @endif
    @endif

    @if ($duty->status === \App\Enums\DutyStatus::SwapRequested && $duty->swaps->isEmpty())
        <p class="mt-2 text-sm text-text/70">{{ __("No one took this swap in time, so you're still on this duty.") }}</p>
    @endif
</div>
