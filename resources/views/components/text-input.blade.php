@props(['disabled' => false])

{{-- Tap height 44px, 16px text (stops iOS zoom), visible primary focus ring, token colours for both themes. --}}
<input
    @disabled($disabled)
    {{ $attributes->merge(['class' => 'block w-full min-h-11 rounded-xl border-text/50 bg-surface px-4 py-2.5 text-base text-text shadow-none placeholder:text-text/60 focus:border-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-dark focus:ring-offset-0 disabled:opacity-50']) }}
>
