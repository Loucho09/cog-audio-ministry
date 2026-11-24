{{-- Shared heading block for every homepage section: eyebrow, h2, optional lead.
     tone="stage" is for dark bands (white text). align="left" is for two-column layouts.
     align="split" is centred on mobile and left-aligned from lg (for desktop split layouts). --}}
@props([
    'id',
    'title',
    'eyebrow' => null,
    'lead' => null,
    'align' => 'center',
    'tone' => 'light',
])

@php
    $centered = $align !== 'left';
    $stage = $tone === 'stage';
    $blockAlign = match ($align) {
        'left' => 'text-left',
        'split' => 'mx-auto text-center lg:mx-0 lg:text-left',
        default => 'mx-auto text-center',
    };
    $eyebrowAlign = match ($align) {
        'left' => '',
        'split' => 'justify-center lg:justify-start',
        default => 'justify-center',
    };
@endphp

<div class="max-w-2xl {{ $blockAlign }}">
    @if (filled($eyebrow))
        <p class="inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] {{ $stage ? 'text-white/90' : 'text-accent-text' }} {{ $eyebrowAlign }}">
            <span aria-hidden="true" class="h-0.5 w-8 rounded-full {{ $stage ? 'bg-white/70' : 'bg-accent' }}"></span>
            {{ $eyebrow }}
        </p>
    @endif

    <h2
        id="{{ $id }}"
        class="{{ filled($eyebrow) ? 'mt-4' : '' }} text-3xl font-extrabold leading-tight tracking-tight [text-wrap:balance] sm:text-4xl lg:text-5xl {{ $stage ? 'text-white' : 'text-text' }}"
    >{{ $title }}</h2>

    @if (filled($lead))
        <p class="mt-4 text-base leading-relaxed sm:text-lg {{ $stage ? 'text-white/90' : 'text-text/80' }}">{{ $lead }}</p>
    @endif
</div>
