@props(['images' => collect()])

@php
    $images = collect($images)->values();
    $allPlaceholders = $images->isNotEmpty() && $images->every(fn ($i) => $i['placeholder']);
    // Only real photos open in the lightbox; placeholders stay plain images.
    $viewable = $images->reject(fn ($i) => $i['placeholder'])->values();
    $viewIndex = 0;
    $controlClasses = 'rounded-full bg-surface p-2.5 text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2';
@endphp

<section id="gallery" aria-labelledby="gallery-title" class="bg-surface py-16 sm:py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="gallery-title" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Gallery</h2>
            @if ($allPlaceholders)
                <p class="mt-4 text-sm text-text/60">Photos coming soon. These are placeholders.</p>
            @endif
        </div>

        @if ($images->isEmpty())
            <p class="mt-12 text-center text-base text-text/70">Photos coming soon.</p>
        @elseif ($allPlaceholders)
            <ul class="mt-12 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
                @foreach ($images as $image)
                    <li>
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-lg object-cover">
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
                        // Everything outside the dialog's branch of the DOM becomes inert.
                        let node = this.$refs.dialog;
                        while (node && node !== document.body) {
                            Array.from(node.parentElement.children).forEach((sib) => {
                                if (sib !== node && !sib.hasAttribute('inert') && sib.tagName !== 'SCRIPT') {
                                    sib.setAttribute('inert', '');
                                    this.inerted.push(sib);
                                }
                            });
                            node = node.parentElement;
                        }
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
                <ul class="mt-12 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
                    @foreach ($images as $image)
                        <li>
                            @if ($image['placeholder'])
                                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-lg object-cover">
                            @else
                                <button
                                    type="button"
                                    x-on:click="show({{ $viewIndex++ }}, $el)"
                                    aria-label="View larger photo: {{ $image['alt'] }}"
                                    class="block w-full rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2"
                                >
                                    <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-lg object-cover">
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <div
                    x-ref="dialog"
                    x-show="open"
                    x-cloak
                    x-on:keydown.tab="trapTab($event)"
                    role="dialog"
                    aria-modal="true"
                    x-bind:aria-labelledby="(images[current]?.title || images[current]?.alt) ? 'gallery-caption' : null"
                    x-bind:aria-label="(images[current]?.title || images[current]?.alt) ? null : 'Photo viewer'"
                    class="fixed inset-0 z-50 flex overflow-y-auto bg-text/95 p-4"
                    x-on:click.self="close()"
                >
                    <div class="m-auto flex w-full max-w-4xl flex-col items-center" x-on:click.self="close()">
                        <figure class="flex flex-col items-center">
                            <img x-bind:src="images[current]?.url" x-bind:alt="images[current]?.alt ?? ''" class="max-h-[70dvh] w-auto max-w-full rounded-lg object-contain">
                            <figcaption id="gallery-caption" aria-live="polite" class="mt-3 text-center text-sm text-surface" x-text="images[current]?.title || images[current]?.alt"></figcaption>
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
            </div>
        @endif
    </div>
</section>
