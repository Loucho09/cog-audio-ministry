{{-- Soft "stage light" glow. Absolutely positioned: add size and position classes where it is used
     (e.g. class="-left-24 top-0 h-80 w-80"). Decorative, never receives pointer events. --}}
@props(['tone' => 'primary'])

<div
    aria-hidden="true"
    {{ $attributes->class([
        'pointer-events-none absolute -z-10 rounded-full blur-[100px]',
        'bg-primary/35' => $tone === 'primary',
        'bg-accent/30' => $tone === 'accent',
    ]) }}
></div>
