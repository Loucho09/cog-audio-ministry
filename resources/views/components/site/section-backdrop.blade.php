{{-- Decorative "stage" backdrop for homepage sections. Purely visual: never receives pointer events,
     never changes text colour or layout. Use it as the FIRST child of a section that has
     `relative isolate overflow-hidden` (the -z-10 layer then sits above the section background
     and below the content).

     Props:
     - variant: 'spotlight-left' | 'rings' | 'cone' | 'vignette' | 'grid' | 'floor'. Each maps to a `.backdrop-*` class in
       resources/css/app.css (gradients and masks live there because they are too long for
       readable Tailwind class strings). Alpha values are CSS variables with a `.dark` override.
     - fadeFrom: 'surface' | 'background' | null. Adds a short soft edge at the top that fades from
       the previous section's colour into transparent, so section borders feel smooth.
       Usage: fade-from="surface" (or omit it for no edge).
     - mirror: true flips the glow/rings horizontally (e.g. :mirror="true" on the team section).

     Masks only use alpha, so the black stops in app.css are not a design colour.
     Keep every class name below as a full literal (never "backdrop-".$variant): Tailwind only keeps
     @layer components classes it can find written out in scanned files. --}}
@props([
    'variant' => 'spotlight-left',
    'fadeFrom' => null,
    'mirror' => false,
])

@php
    $fadeClass = match ($fadeFrom) {
        'surface' => 'from-surface',
        'background' => 'from-background',
        default => null,
    };
@endphp

<div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10 overflow-hidden">
    {{-- The atmosphere layer. Flipped with scale-x when mirrored. --}}
    <div @class([
        'absolute inset-0',
        'backdrop-spotlight-left' => $variant === 'spotlight-left',
        'backdrop-rings' => $variant === 'rings',
        'backdrop-cone' => $variant === 'cone',
        'backdrop-vignette' => $variant === 'vignette',
        'backdrop-grid' => $variant === 'grid',
        'backdrop-floor' => $variant === 'floor',
        'scale-x-[-1]' => $mirror,
    ])></div>

    {{-- Soft top edge: previous section's colour fading to transparent. Drawn above the atmosphere. --}}
    @if ($fadeClass)
        <div class="absolute inset-x-0 top-0 h-24 bg-gradient-to-b {{ $fadeClass }} to-transparent sm:h-32"></div>
    @endif
</div>
