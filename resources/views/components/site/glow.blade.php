{{-- Soft "stage light" glow. Absolutely positioned: add size and position classes where it is used
     (e.g. class="-left-24 top-0 h-80 w-80"). Decorative, never receives pointer events.
     Uses a radial-gradient (.glow-primary / .glow-accent in app.css), not a blur() filter, for mobile GPUs. --}}
@props(['tone' => 'primary'])

<div
    aria-hidden="true"
    {{ $attributes->class([
        'pointer-events-none absolute -z-10 rounded-full',
        'glow-primary' => $tone === 'primary',
        'glow-accent' => $tone === 'accent',
    ]) }}
></div>
