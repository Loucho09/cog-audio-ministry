@props([
    'variant' => 'primary',
    'size' => 'default',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex shrink-0 items-center justify-center gap-2 whitespace-nowrap rounded-full font-semibold tracking-wide transition duration-200 motion-reduce:transform-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2';

    // Both sizes are at least 44px tall for touch.
    $sizes = [
        'default' => 'min-h-11 px-4 py-2 text-sm',
        'lg' => 'min-h-11 px-6 py-3 text-base',
    ];

    $variants = [
        // Filled blue: AA in both themes (primary-dark flips with the theme). Lifts and glows on hover.
        'primary' => 'bg-primary-dark text-surface shadow-md shadow-primary-dark/25 hover:-translate-y-0.5 hover:brightness-110 hover:shadow-lg hover:shadow-primary-dark/40',
        'secondary' => 'border border-primary-dark bg-surface text-primary-dark shadow-sm hover:-translate-y-0.5 hover:bg-background hover:shadow-md',
        // For the dark hero photo and dark stage panels: white outline and text, always.
        'outline-light' => 'border border-white/70 bg-transparent text-white hover:-translate-y-0.5 hover:border-white hover:bg-white/10 focus-visible:!ring-white focus-visible:!ring-offset-black',
    ];

    // Unknown size or variant names fall back to the defaults instead of erroring.
    $classes = $base.' '.($sizes[$size] ?? $sizes['default']).' '.($variants[$variant] ?? $variants['primary']);
@endphp

{{-- Renders a link when href is set, otherwise a button (type defaults to "button"; pass type="submit" for forms). Extra classes are merged in. --}}
@if (filled($href))
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
