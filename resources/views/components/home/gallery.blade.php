@props(['images' => collect()])

@php
    $images = collect($images)->values();
    $allPlaceholders = $images->isNotEmpty() && $images->every(fn ($i) => $i['placeholder']);
    // Only real photos open in the lightbox; placeholders stay plain images.
    $viewable = $images->reject(fn ($i) => $i['placeholder'])->values();
    $viewIndex = 0;
    $controlClasses = 'rounded-console border border-text/20 bg-surface p-2.5 text-text transition-colors duration-200 hover:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2';
    $tileClasses = 'aspect-[4/3] w-full rounded-console border border-text/10';
@endphp

<x-section id="gallery">
    <div data-fx="fader-heading">
        <x-section-heading
            id="gallery"
            channel="05"
            eyebrow="Gallery"
            title="Behind the board."
            :description="$allPlaceholders ? 'Photos coming soon. These are placeholders.' : null"
        />
    </div>

    {{-- Desktop: one horizontal track (the motion layer pins it later; plain scroll-snap works without JS).
         Mobile: a normal two-column grid. --}}
    @if ($images->isEmpty())
        <div class="mt-12 lg:mt-16">
            <p class="font-mono text-sm text-text/70">Photos coming soon.</p>
            {{-- TODO: owner to add real ministry photos in Filament (Gallery). --}}
            <ul class="mt-6 grid grid-cols-2 gap-6 lg:flex lg:gap-8 lg:overflow-x-auto" data-fx="hscroll">
                @foreach (range(1, 4) as $n)
                    <li class="flex aspect-[4/3] flex-col items-start justify-end gap-1 rounded-console border border-dashed border-text/20 bg-surface p-4 lg:w-[26rem] lg:shrink-0">
                        <span class="t-label text-text/60">Photo pending</span>
                        <span class="font-mono text-xs text-text/50">Crew at the board</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @elseif ($allPlaceholders)
        <ul class="mt-12 grid grid-cols-2 gap-6 lg:mt-16 lg:flex lg:gap-8 lg:snap-x lg:overflow-x-auto lg:pb-4" data-fx="hscroll">
            @foreach ($images as $image)
                <li class="lg:w-[26rem] lg:shrink-0 lg:snap-start">
                    <x-skeleton-image src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="{{ $tileClasses }}"/>
                </li>
            @endforeach
        </ul>
    @else
            <div
                x-data="{
                    images: @js($viewable),
                    open: false,
                    current: 0,
                    trigger: null,
                    inerted: [],
                    show(i, el) {
                        this.current = i;
                        this.trigger = el;
                        this.open = true;
                        document.body.classList.add('overflow-hidden');
                        this.makeInert();
                        this.$nextTick(() => this.$refs.close.focus());
                    },
                    close() {
                        this.open = false;
                        document.body.classList.remove('overflow-hidden');
                        this.restoreInert();
                        this.$nextTick(() => this.trigger && this.trigger.focus());
                    },
                    makeInert() {
                        // The dialog is teleported to <body>, so it is a direct child there.
                        // Every other body child (skip link, header, main, footer) becomes inert.
                        const dialog = this.$refs.dialog;
                        Array.from(document.body.children).forEach((el) => {
                            if (el === dialog || el.tagName === 'SCRIPT' || el.hasAttribute('inert')) { return; }
                            el.setAttribute('inert', '');
                            this.inerted.push(el);
                        });
                    },
                    restoreInert() {
                        this.inerted.forEach((el) => el.removeAttribute('inert'));
                        this.inerted = [];
                    },
                    trapTab(e) {
                        const items = Array.from(this.$refs.dialog.querySelectorAll('button')).filter((el) => el.getClientRects().length > 0);
                        if (items.length === 0) { return; }
                        const first = items[0];
                        const last = items[items.length - 1];
                        const active = document.activeElement;
                        if (!this.$refs.dialog.contains(active)) {
                            e.preventDefault();
                            first.focus();
                        } else if (e.shiftKey && active === first) {
                            e.preventDefault();
                            last.focus();
                        } else if (!e.shiftKey && active === last) {
                            e.preventDefault();
                            first.focus();
                        }
                    },
                    next() { if (this.images.length > 1) { this.current = (this.current + 1) % this.images.length; } },
                    prev() { if (this.images.length > 1) { this.current = (this.current - 1 + this.images.length) % this.images.length; } },
                }"
                x-on:keydown.escape.window="open && close()"
                x-on:keydown.arrow-right.window="open && next()"
                x-on:keydown.arrow-left.window="open && prev()"
            >
                <ul class="mt-12 grid grid-cols-2 gap-6 lg:mt-16 lg:flex lg:snap-x lg:gap-8 lg:overflow-x-auto lg:pb-4" data-fx="hscroll">
                    @foreach ($images as $image)
                        @php
                            // Rendered width: half the viewport on mobile, a fixed track card on desktop.
                            $sizes = '(min-width: 1024px) 26rem, 50vw';
                        @endphp
                        <li class="aspect-[4/3] lg:w-[26rem] lg:shrink-0 lg:snap-start">
                            @if ($image['placeholder'])
                                <x-skeleton-image src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="h-full w-full rounded-console border border-text/10"/>
                            @else
                                <button
                                    type="button"
                                    x-on:click="show({{ $viewIndex++ }}, $el)"
                                    aria-label="View larger photo: {{ $image['alt'] }}"
                                    class="photo-tone group relative block h-full w-full rounded-console border border-text/10 bg-text/5 transition-colors duration-300 hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                                >
                                    {{-- Grid uses the WebP thumbnails; the full photo is only loaded by the lightbox. Lazy: far below the fold. --}}
                                    @if ($image['thumb'])
                                        <x-skeleton-image src="{{ $image['thumb'] }}" srcset="{{ $image['srcset'] }}" sizes="{{ $sizes }}" alt="{{ $image['alt'] }}" class="h-full w-full"/>
                                    @else
                                        <x-skeleton-image src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" class="h-full w-full"/>
                                    @endif
                                    {{-- Mono caption strip. Decorative: the button's aria-label already names the photo. --}}
                                    <span aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 z-10 bg-console/80 px-3 py-2 text-left font-mono text-[11px] uppercase tracking-[0.12em] text-white">
                                        <span class="line-clamp-1">{{ $image['title'] ?: $image['alt'] }}</span>
                                    </span>
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>

                {{-- Teleported to <body> so the fixed dialog is not trapped inside this section's stacking context
                     (the section uses isolate, and the navbar sits above it at z-50). The dialog uses z-[60]. --}}
                <template x-teleport="body">
                    <div
                        x-ref="dialog"
                        x-show="open"
                        x-cloak
                        x-on:keydown.tab="trapTab($event)"
                        role="dialog"
                        aria-modal="true"
                        data-lenis-prevent
                        x-bind:aria-labelledby="(images[current]?.title || images[current]?.alt) ? 'gallery-caption' : null"
                        x-bind:aria-label="(images[current]?.title || images[current]?.alt) ? null : 'Photo viewer'"
                        class="fixed inset-0 z-[60] flex overflow-y-auto bg-text/95 p-4 dark:bg-background/95"
                        x-on:click.self="close()"
                    >
                        <div class="m-auto flex w-full max-w-4xl flex-col items-center" x-on:click.self="close()">
                            <figure class="flex flex-col items-center">
                                {{-- Only rendered while open, so the full-size photo is not downloaded before anyone opens the viewer. --}}
                                <template x-if="open">
                                    <img x-bind:src="images[current]?.url" x-bind:alt="images[current]?.alt ?? ''" class="max-h-[70dvh] w-auto max-w-full rounded-lg object-contain">
                                </template>
                                <figcaption id="gallery-caption" aria-live="polite" class="mt-3 text-center text-sm text-surface dark:text-text" x-text="images[current]?.title || images[current]?.alt"></figcaption>
                            </figure>

                            @if ($viewable->count() > 1)
                                {{-- Below the image on small screens; overlaid at the sides from sm up. --}}
                                <div class="mt-4 flex gap-6 sm:contents">
                                    <button type="button" x-on:click="prev()" aria-label="Previous photo" class="{{ $controlClasses }} sm:absolute sm:left-4 sm:top-1/2 sm:-translate-y-1/2">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                                    </button>
                                    <button type="button" x-on:click="next()" aria-label="Next photo" class="{{ $controlClasses }} sm:absolute sm:right-4 sm:top-1/2 sm:-translate-y-1/2">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                                    </button>
                                </div>
                            @endif
                        </div>

                        <button type="button" x-ref="close" x-on:click="close()" aria-label="Close photo viewer" class="{{ $controlClasses }} absolute right-4 top-4">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                </template>
            </div>
    @endif
</x-section>
