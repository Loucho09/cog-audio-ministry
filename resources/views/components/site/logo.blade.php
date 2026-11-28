{{-- Brand mark: the official logo when public/images/logo.png exists, otherwise a "COG" text placeholder.
     Same rule as the public navbar. Decorative (alt=""): the name is always printed next to it. --}}
@php
    $hasLogo = file_exists(public_path('images/logo-40.webp'));
@endphp

@if ($hasLogo)
    <img
        src="{{ asset('images/logo-40.webp') }}"
        srcset="{{ asset('images/logo-40.webp') }} 1x, {{ asset('images/logo-80.webp') }} 2x, {{ asset('images/logo-120.webp') }} 3x"
        alt=""
        width="40"
        height="40"
        {{ $attributes->class('h-10 w-10 shrink-0 rounded-full object-contain') }}
    >
@else
    <span
        aria-hidden="true"
        {{ $attributes->class('flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary-dark text-xs font-semibold tracking-wide text-surface') }}
    >COG</span>
@endif
