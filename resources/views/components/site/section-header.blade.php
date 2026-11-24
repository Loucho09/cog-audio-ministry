{{-- Shared heading block for every homepage section: eyebrow, h2, optional lead.
     tone="stage" is for dark bands (white text). align="left" is for two-column layouts. --}}
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
@endphp

<div class="max-w-2xl {{ $centered ? 'mx-auto text-center' : 'text-left' }}">
    @if (filled($eyebrow))
        <p class="inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] {{ $stage ? 'text-white/80' : 'text-accent-text' }} {{ $centered ? 'justify-center' : '' }}">
            <span aria-hidden="true" class="h-0.5 w-8 rounded-full {{ $stage ? 'bg-white/70' : 'bg-accent' }}"></span>
            {{ $eyebrow }}
        </p>
    @endif

    <h2
        id="{{ $id }}"
        class="{{ filled($eyebrow) ? 'mt-4' : '' }} text-3xl font-extrabold leading-tight tracking-tight [text-wrap:balance] sm:text-4xl lg:text-5xl {{ $stage ? 'text-white' : 'text-text' }}"
    >{{ $title }}</h2>

    @if (filled($lead))
        <p class="mt-4 text-base leading-relaxed sm:text-lg {{ $stage ? 'text-white/85' : 'text-text/70' }}">{{ $lead }}</p>
    @endif
</div>
