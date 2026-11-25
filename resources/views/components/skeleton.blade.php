{{--
    Shared skeleton placeholder for async content (duty roster, announcements, and later features).
    Always aria-hidden: screen readers get the real content (or a live-region message) instead.
    Styling: the `.skeleton` shimmer plus `.skeleton-text` / `.skeleton-circle` helpers in resources/css/app.css.

    Usage (examples are plain text here: a nested comment marker would end this comment early):
        x-skeleton with no props                  one text line (the default)
        x-skeleton variant="line" :lines="3"      three lines, the last one shorter
        x-skeleton variant="block" height="10rem" rounded block (photo, chart)
        x-skeleton variant="circle" :width="48"   avatar; numbers are px
        x-skeleton variant="card" :lines="2"      bordered card: title bar + lines

    Props:
        variant: line | block | circle | card (unknown values fall back to line)
        width, height: CSS lengths ("12rem", "60%", "120px") or numbers (px). Anything else is ignored.
        lines: how many text lines (line and card variants only, 1 to 6)
        class: extra classes on the wrapper

    Do not use it where nothing loads async: server-rendered content arrives complete.
--}}
@props([
    'variant' => 'line',
    'width' => null,
    'height' => null,
    'lines' => 1,
    'class' => null,
])

@php
    $variants = ['line', 'block', 'circle', 'card'];
    $variant = in_array($variant, $variants, true) ? $variant : 'line';

    // Only plain CSS lengths reach the style attribute (numbers become px).
    $length = function ($value) {
        if (is_int($value) || is_float($value)) {
            return $value.'px';
        }

        return is_string($value) && preg_match('/^\d+(\.\d+)?(px|rem|em|%|svh|vw|vh)?$/', $value)
            ? $value
            : null;
    };

    $width = $length($width);
    $height = $length($height);
    $lines = max(1, min(6, (int) $lines));

    // A circle is square: one size value is enough.
    if ($variant === 'circle') {
        $width = $height = $width ?? $height;
    }

    $style = collect([
        $width ? "width: {$width}" : null,
        $height ? "height: {$height}" : null,
    ])->filter()->implode('; ');
@endphp

@switch($variant)
    @case('block')
        <div
            aria-hidden="true"
            data-skeleton="block"
            @if ($style !== '') style="{{ $style }}" @endif
            {{ $attributes->class(['skeleton rounded-xl', $height ? '' : 'h-24', $width ? '' : 'w-full', $class]) }}
        ></div>
        @break

    @case('circle')
        <div
            aria-hidden="true"
            data-skeleton="circle"
            @if ($style !== '') style="{{ $style }}" @endif
            {{ $attributes->class(['skeleton skeleton-circle', $class]) }}
        ></div>
        @break

    @case('card')
        <div
            aria-hidden="true"
            data-skeleton="card"
            @if ($style !== '') style="{{ $style }}" @endif
            {{ $attributes->class(['rounded-2xl border border-text/15 bg-surface p-6 shadow-sm', $width ? '' : 'w-full', $class]) }}
        >
            <div class="skeleton skeleton-text h-5 w-1/2"></div>
            <div class="mt-5 space-y-3">
                @for ($i = 0; $i < $lines; $i++)
                    <div class="skeleton skeleton-text {{ $i === $lines - 1 && $lines > 1 ? 'w-2/3' : 'w-full' }}"></div>
                @endfor
            </div>
        </div>
        @break

    @default
        <div
            aria-hidden="true"
            data-skeleton="line"
            @if ($style !== '') style="{{ $style }}" @endif
            {{ $attributes->class(['flex flex-col gap-3', $class]) }}
        >
            @for ($i = 0; $i < $lines; $i++)
                <div class="skeleton skeleton-text {{ $i === $lines - 1 && $lines > 1 ? 'w-2/3' : 'w-full' }}"></div>
            @endfor
        </div>
@endswitch
