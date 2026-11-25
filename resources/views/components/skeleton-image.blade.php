{{--
    Photo with a shimmer placeholder.
    - The wrapper keeps the photo's box (from width/height, or an explicit aspect like "4/3"), so nothing shifts when it loads.
    - The <img> starts at opacity-0 and fades in (300ms) once loaded. Cached images are caught in init(), because load can fire before Alpine starts.
    - On error the shimmer stops and a calm placeholder shows (role="img" with the alt text, or aria-hidden when alt is "").
    - No JS: the <img> is visible by default (Alpine only adds opacity-0 while it is still loading), so the photo always shows.
    Props: src, alt (required; "" for decorative), width, height, aspect, rounded (wrapper), loading (lazy|eager), class (wrapper), img-class (img).
    Other attributes (sizes, srcset, fetchpriority, aria-hidden, ...) go on the <img>.
--}}
@props([
    'src',
    'alt' => null,
    'width' => null,
    'height' => null,
    'aspect' => null,
    'rounded' => null,
    'loading' => 'lazy',
    'class' => null,
    'imgClass' => null,
])

@php
    if ($alt === null) {
        throw new \InvalidArgumentException('x-skeleton-image needs an alt attribute (use alt="" for decorative photos).');
    }

    $ratio = filled($aspect)
        ? str_replace('/', ' / ', (string) $aspect)
        : (filled($width) && filled($height) ? "{$width} / {$height}" : null);
    $decorative = $alt === '';
@endphp

<div
    x-data="{
        state: 'loading',
        init() {
            const img = this.$refs.img;
            if (img.complete) {
                this.state = img.naturalWidth > 0 ? 'loaded' : 'error';
            }
        },
    }"
    :data-state="state"
    data-skeleton-image
    @if ($ratio) style="aspect-ratio: {{ $ratio }}" @endif
    class="relative overflow-hidden skeleton {{ $rounded }} {{ $class }}"
>
    <img
        x-ref="img"
        src="{{ $src }}"
        alt="{{ $alt }}"
        @if (filled($width)) width="{{ $width }}" @endif
        @if (filled($height)) height="{{ $height }}" @endif
        loading="{{ $loading }}"
        decoding="async"
        {{ $attributes->except(['img-class']) }}
        class="absolute inset-0 h-full w-full object-cover transition-opacity duration-300 ease-out {{ $imgClass }}"
        :class="{ 'opacity-0': state !== 'loaded', 'hidden': state === 'error' }"
        x-on:load="state = 'loaded'"
        x-on:error="state = 'error'"
    >

    <div
        class="hidden absolute inset-0 items-center justify-center bg-text/5 text-text/40"
        :class="{ 'hidden': state !== 'error', 'flex': state === 'error' }"
        @if ($decorative) aria-hidden="true" @else role="img" aria-label="{{ $alt }}" @endif
    >
        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" />
        </svg>
    </div>
</div>
