{{-- The hero's Join us button: the one attention-seeking control on the page (spec: brain/Features/hero-console-story.md,
     brand exception: brain/Decisions/hero-join-brand-exception.md). resources/js/home/join-button.js adds the motion
     (meter bars, sonar, scramble, magnet, click burst). Without JS it is a plain link with static meter bars;
     the running border light and the tooltip fade-in are CSS. Reduced motion keeps only the static meter bars. --}}
@props(['href'])

<div data-join-cta class="join-cta relative inline-flex w-full sm:w-auto">
    <p data-join-tip class="join-tip absolute bottom-full left-0 mb-3 inline-flex items-center gap-2 whitespace-nowrap rounded-full border border-white/15 bg-console/80 px-3 py-1.5 font-mono text-[11px] tracking-[0.04em] text-white/85">
        <span aria-hidden="true" class="relative flex h-2 w-2 shrink-0">
            <span class="join-tip-pulse absolute inset-0 rounded-full bg-signal"></span>
            <span class="relative h-2 w-2 rounded-full bg-signal"></span>
        </span>
        Volunteers needed — sound, stream, stage
    </p>

    {{-- Sonar rings and click burst: decorative, animated from JS. --}}
    <span aria-hidden="true" class="pointer-events-none absolute inset-0">
        @for ($i = 0; $i < 2; $i++)
            <span data-join-ring class="join-ring absolute inset-0 rounded-console"></span>
        @endfor
        @for ($i = 0; $i < 3; $i++)
            <span data-join-burst class="join-ring absolute left-1/2 top-1/2 h-12 w-12 -translate-x-1/2 -translate-y-1/2 rounded-full"></span>
        @endfor
    </span>

    <a
        href="{{ $href }}"
        data-join-button
        class="join-btn relative inline-flex min-h-12 w-full items-center justify-center gap-3 rounded-console px-6 font-mono text-sm font-medium uppercase tracking-[0.12em] text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-console sm:w-auto"
    >
        <span aria-hidden="true" data-join-meter class="join-meter">
            @for ($i = 0; $i < 5; $i++)
                <i></i>
            @endfor
        </span>
        <span data-join-label class="whitespace-nowrap">Join Us</span>
        <svg data-join-arrow class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14" />
            <path d="M13 18l6-6" />
            <path d="M13 6l6 6" />
        </svg>
        <svg class="join-border" aria-hidden="true"><rect x="0" y="0" width="100%" height="100%" rx="6" pathLength="100" /></svg>
    </a>
</div>
