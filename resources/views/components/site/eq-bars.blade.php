{{-- Decorative equalizer: six bars that bounce when motion is allowed. Static (varied heights) under reduced motion.
     Purely decorative, so aria-hidden. --}}
@php
    // Static heights give the still image its shape; the animation scales each bar from its base.
    $bars = [
        ['h' => 'h-2', 'delay' => '0s'],
        ['h' => 'h-5', 'delay' => '0.3s'],
        ['h' => 'h-3', 'delay' => '0.6s'],
        ['h' => 'h-6', 'delay' => '0.15s'],
        ['h' => 'h-4', 'delay' => '0.45s'],
        ['h' => 'h-2.5', 'delay' => '0.75s'],
    ];
@endphp

<span aria-hidden="true" {{ $attributes->class('inline-flex h-6 items-end gap-[3px]') }}>
    @foreach ($bars as $bar)
        <span
            class="w-[3px] origin-bottom rounded-full bg-gradient-to-t from-primary to-accent {{ $bar['h'] }} motion-safe:animate-equalizer"
            style="animation-delay: -{{ $bar['delay'] }}"
        ></span>
    @endforeach
</span>
