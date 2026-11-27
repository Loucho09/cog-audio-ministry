{{-- Marquee band right under the hero: what the team does, in big type, on the dark console band (same in both themes).
     Decorative only (aria-hidden): every word is already said in the page copy.
     resources/js/home/effects.js (velocityMarquee) moves the rows; speed and skew follow scroll velocity.
     Each row holds two identical copies so it loops seamlessly. Without JS or with reduced motion it sits still. --}}
@php
    $words = ['Sound', 'Stage', 'Livestream', 'Worship', 'Serve'];
    $rows = [
        ['direction' => 1, 'speed' => 45, 'style' => 'fill'],
        ['direction' => -1, 'speed' => 32, 'style' => 'outline'],
    ];
@endphp

<div data-marquee data-fx="marquee" aria-hidden="true" class="relative isolate select-none overflow-hidden border-y border-white/10 bg-console py-8 text-white sm:py-10">
    {{-- dB scale along the top edge, like the meter bridge on a desk. --}}
    <div class="absolute inset-x-0 top-0 flex h-2 justify-between px-5 opacity-30 sm:px-8">
        @for ($tick = 0; $tick < 48; $tick++)
            <span class="w-px bg-white {{ $tick % 6 === 0 ? 'h-2' : 'h-1' }}"></span>
        @endfor
    </div>

    @foreach ($rows as $row)
        <div data-marquee-row data-direction="{{ $row['direction'] }}" data-speed="{{ $row['speed'] }}" class="flex w-max will-change-transform {{ $loop->first ? '' : 'mt-2 sm:mt-3' }}">
            @for ($copy = 0; $copy < 2; $copy++)
                <div class="flex shrink-0 items-center">
                    @for ($repeat = 0; $repeat < 2; $repeat++)
                        @foreach ($words as $word)
                            <span class="px-6 text-5xl font-bold uppercase leading-none tracking-[-0.03em] sm:px-10 sm:text-7xl lg:text-[6.5rem] {{ $row['style'] === 'fill' ? 'text-white' : 'marquee-outline' }}">{{ $word }}</span>
                            {{-- Separator: channel number in mono plus a small LED, the only blue in the band. --}}
                            <span class="flex shrink-0 items-center gap-2 font-mono text-[11px] tracking-[0.12em] text-white/40">
                                <span class="h-1.5 w-1.5 rounded-full {{ $row['style'] === 'fill' && $loop->first ? 'bg-signal' : 'bg-white/30' }}"></span>
                                CH {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                        @endforeach
                    @endfor
                </div>
            @endfor
        </div>
    @endforeach
</div>
