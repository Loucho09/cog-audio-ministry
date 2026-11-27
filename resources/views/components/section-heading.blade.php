@props([
    // Section number shown as "CH 01". Omit for unnumbered sections.
    'channel' => null,
    // Short mono label after the channel, e.g. "About".
    'eyebrow' => null,
    'title',
    'description' => null,
    // Used for the h2 id: "{id}-title".
    'id' => null,
    // left: stacked (default). split: heading in cols 1-7, description in cols 9-12 at lg.
    'align' => 'left',
    // light or console (dark band). Changes the muted text colours only.
    'tone' => 'light',
])

@php
    $isSplit = $align === 'split';
    $isConsole = $tone === 'console';
    $labelColor = $isConsole ? 'text-white/60' : 'text-text/60';
    $descColor = $isConsole ? 'text-white/70' : 'text-text/70';
    $label = collect([filled($channel) ? 'CH '.$channel : null, $eyebrow])->filter()->implode(' — ');
@endphp

<header class="{{ $isSplit ? 'grid gap-6 lg:grid-cols-12 lg:items-end lg:gap-8' : 'max-w-3xl' }}">
    <div class="{{ $isSplit ? 'lg:col-span-7' : '' }}">
        @if (filled($label))
            <div class="flex items-center gap-4 {{ $labelColor }}">
                <p class="t-label">{{ $label }}</p>
                <span aria-hidden="true" class="h-px w-10 bg-current opacity-40"></span>
                <svg
                    aria-hidden="true"
                    focusable="false"
                    viewBox="0 0 64 8"
                    class="h-2 w-16 shrink-0 opacity-50"
                >
                    <path d="M0.5 0V8M8.5 4V8M16.5 4V8M24.5 4V8M32.5 0V8M40.5 4V8M48.5 4V8M56.5 4V8M63.5 0V8" stroke="currentColor" stroke-width="1" fill="none" />
                </svg>
            </div>
        @endif

        <h2
            @if (filled($id)) id="{{ $id }}-title" @endif
            class="t-h2 mt-6 font-semibold"
        >{{ $title }}</h2>
    </div>

    @if (filled($description))
        <p class="t-lead mt-6 {{ $descColor }} {{ $isSplit ? 'lg:col-span-4 lg:col-start-9 lg:mt-0' : 'max-w-2xl' }}">
            {{ $description }}
        </p>
    @endif
</header>
