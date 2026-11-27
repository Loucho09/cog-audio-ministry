@props([
    // When set, renders an <a>. Otherwise renders a <button type="button"> (override type via attributes).
    'href' => null,
    // primary: solid blue. ghost: hairline outline on light/dark surfaces. on-dark: outline for console bands.
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        // primary-dark passes AA with white text on light; dark theme uses the light blue, so text flips to console.
        'ghost' => 'border border-text/20 text-text hover:border-primary',
        'on-dark' => 'border border-white/25 text-white hover:border-white/60',
        default => 'bg-primary-dark text-white hover:brightness-110 dark:bg-primary dark:text-console',
    };

    $classes = [
        'group relative inline-flex min-h-12 items-center justify-center gap-2 overflow-hidden rounded-console px-6',
        'font-mono text-sm font-medium uppercase tracking-[0.12em] transition-colors',
        'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal focus-visible:ring-offset-2 focus-visible:ring-offset-background',
        $variantClasses,
    ];
@endphp

@if (filled($href))
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        {{ $slot }}
        {{-- Fader underline: scales in from the left on hover. Transform only. --}}
        <span aria-hidden="true" class="pointer-events-none absolute inset-x-6 bottom-2 h-0.5 origin-left scale-x-0 bg-current transition-transform duration-[600ms] ease-out group-hover:scale-x-100 motion-reduce:transition-none"></span>
    </a>
@else
    <button {{ $attributes->class($classes)->merge(['type' => 'button']) }}>
        {{ $slot }}
        <span aria-hidden="true" class="pointer-events-none absolute inset-x-6 bottom-2 h-0.5 origin-left scale-x-0 bg-current transition-transform duration-[600ms] ease-out group-hover:scale-x-100 motion-reduce:transition-none"></span>
    </button>
@endif
