{{-- Marquee band between the hero and the impact strip: what the team does, in big type.
     Decorative only (aria-hidden): every word here is already said in the page copy.
     resources/js/home/effects.js (velocityMarquee) moves the rows; their speed and skew follow scroll velocity.
     Each row holds two identical copies so it can loop seamlessly. Without JS or with reduced motion it sits still. --}}
@php
    $words = ['Sound', 'Stage', 'Livestream', 'Worship', 'Serve'];
@endphp

<div data-marquee aria-hidden="true" class="relative isolate select-none overflow-hidden border-y border-text/10 bg-surface py-6 sm:py-8">
    <span class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary to-transparent"></span>

    @foreach ([['direction' => 1, 'speed' => 55, 'style' => 'fill'], ['direction' => -1, 'speed' => 40, 'style' => 'outline']] as $row)
        <div data-marquee-row data-direction="{{ $row['direction'] }}" data-speed="{{ $row['speed'] }}" class="flex w-max will-change-transform {{ $loop->first ? '' : 'mt-1 sm:mt-2' }}">
            @for ($copy = 0; $copy < 2; $copy++)
                <div class="flex shrink-0 items-center">
                    @for ($repeat = 0; $repeat < 2; $repeat++)
                        @foreach ($words as $word)
                            <span class="px-5 text-5xl font-extrabold uppercase leading-none tracking-tight sm:px-8 sm:text-7xl lg:text-8xl {{ $row['style'] === 'fill' ? 'text-text' : 'marquee-outline' }}">{{ $word }}</span>
                            <span class="h-3 w-3 shrink-0 rounded-full sm:h-4 sm:w-4 {{ $loop->odd ? 'bg-accent' : 'bg-primary' }}"></span>
                        @endforeach
                    @endfor
                </div>
            @endfor
        </div>
    @endforeach
</div>
