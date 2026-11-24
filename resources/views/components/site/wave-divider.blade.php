{{-- Thin sine-wave line with a primary-to-accent stroke. Decorative only.
     Pass a unique gradientId when the same page uses several dividers. --}}
@props(['gradientId' => 'wave-gradient'])

<svg
    aria-hidden="true"
    focusable="false"
    viewBox="0 0 1200 40"
    preserveAspectRatio="none"
    {{ $attributes->class('block h-6 w-full opacity-60 sm:h-8') }}
>
    <defs>
        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" style="stop-color: rgb(var(--color-primary))"></stop>
            <stop offset="1" style="stop-color: rgb(var(--color-accent))"></stop>
        </linearGradient>
    </defs>
    <path
        d="M0 20 C60 0 90 40 150 20 S240 0 300 20 S390 40 450 20 S540 0 600 20 S690 40 750 20 S840 0 900 20 S990 40 1050 20 S1140 0 1200 20"
        fill="none"
        stroke="url(#{{ $gradientId }})"
        stroke-width="2"
        stroke-linecap="round"
        vector-effect="non-scaling-stroke"
    />
</svg>
