@props(['active' => false])

@php
// Active = primary-dark text with an accent underline. Inactive = muted text, primary on hover.
$base = 'relative inline-flex min-h-11 items-center whitespace-nowrap rounded-md px-3 text-sm font-medium transition-colors duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark after:absolute after:inset-x-3 after:bottom-1.5 after:h-0.5 after:rounded-full after:bg-accent after:transition-transform after:duration-200';
$classes = $active
    ? $base.' text-primary-dark after:scale-x-100'
    : $base.' text-text/80 hover:text-primary-dark after:origin-left after:scale-x-0 hover:after:scale-x-100';
@endphp

<a {{ $attributes->class($classes) }} @if ($active) aria-current="page" @endif>
    {{ $slot }}
</a>
