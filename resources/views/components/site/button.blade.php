@props([
    'variant' => 'primary',
    'size' => 'default',
    'href' => null,
])

@php
    $base = 'inline-flex shrink-0 items-center justify-center whitespace-nowrap rounded-md font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent focus-visible:ring-offset-2';

    $sizes = [
        'default' => 'px-4 py-2 text-sm',
        'lg' => 'px-6 py-3 text-base',
    ];

    $variants = [
        'primary' => 'bg-primary-dark text-surface hover:bg-text',
        'secondary' => 'border border-primary-dark bg-surface text-primary-dark hover:bg-background',
    ];

    $classes = $base.' '.$sizes[$size].' '.$variants[$variant];
@endphp

{{-- Renders a link when href is set, otherwise a button. Extra classes are merged in. --}}
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
