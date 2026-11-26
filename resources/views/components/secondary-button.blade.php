{{-- Outline style from the public site's secondary button. Button type by default (not submit). Pass type="submit" and loading-text to use it in a form. --}}
@props(['loadingText' => null, 'type' => 'button'])

<x-site.button variant="secondary" :type="$type" {{ $attributes->class(['disabled:cursor-wait disabled:opacity-70', 'group' => filled($loadingText)]) }}>
    @if (filled($loadingText))
        <x-loading-label :loading-text="$loadingText">{{ $slot }}</x-loading-label>
    @else
        {{ $slot }}
    @endif
</x-site.button>
