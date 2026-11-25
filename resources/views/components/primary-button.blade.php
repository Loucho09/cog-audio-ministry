{{--
    Same look as the public site's primary button (x-site.button). Submit by default.
    Pass loading-text="Saving…" to show a spinner and that label while the form submits (resources/js/submit-guard.js).
--}}
@props(['loadingText' => null])

<x-site.button type="submit" {{ $attributes->class(['disabled:cursor-wait disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:brightness-100', 'group' => filled($loadingText)]) }}>
    @if (filled($loadingText))
        <x-loading-label :loading-text="$loadingText">{{ $slot }}</x-loading-label>
    @else
        {{ $slot }}
    @endif
</x-site.button>
