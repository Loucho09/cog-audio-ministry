@props([
    'variant' => 'primary',
    'size' => 'default',
    'href' => null,
    'type' => 'button',
])

@php
    $base = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2';

    $sizes = [
        'default' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $variants = [
        'primary' => 'bg-primary-dark text-surface hover:bg-text',
        'secondary' => 'border border-primary-dark bg-surface text-primary-dark hover:bg-background',
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
