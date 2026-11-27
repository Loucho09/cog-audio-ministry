@props([
    // Anchor id. Also drives aria-labelledby ("{id}-title") that section-heading uses for its h2.
    'id' => null,
    // light: page background. surface: white/dark card layer. console: dark band (both themes).
    'tone' => 'light',
])

@php
    $toneClasses = match ($tone) {
        'surface' => 'bg-surface text-text',
        'console' => 'bg-console text-white',
        default => 'bg-background text-text',
    };
@endphp

<section
    @if (filled($id))
        id="{{ $id }}"
        aria-labelledby="{{ $id }}-title"
    @endif
    {{ $attributes->class(['relative py-24 lg:py-32', $toneClasses]) }}
>
    <div class="mx-auto max-w-7xl px-6 lg:px-8">
        {{ $slot }}
    </div>
</section>
