@props(['images' => collect()])

@php
    $images = collect($images)->values();
    $allPlaceholders = $images->isNotEmpty() && $images->every(fn ($i) => $i['placeholder']);
    // Only real photos open in the lightbox; placeholders stay plain images.
    $viewable = $images->reject(fn ($i) => $i['placeholder'])->values();
    $viewIndex = 0;
    $controlClasses = 'rounded-full bg-surface p-2.5 text-text shadow-lg transition-colors duration-200 hover:bg-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2';
    $tileClasses = 'aspect-[4/3] w-full rounded-3xl object-cover shadow-sm';
@endphp

<section id="gallery" aria-labelledby="gallery-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
    <x-site.section-backdrop variant="vignette" fade-from="surface" />
    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
        <x-site.section-header
            id="gallery-title"
            eyebrow="Photos"
            title="Gallery"
            :lead="$allPlaceholders ? 'Photos coming soon. These are placeholders.' : null"
        />

        @if ($images->isEmpty())
            <p class="mt-12 text-center text-base text-text/70 sm:mt-16">Photos coming soon.</p>
        @elseif ($allPlaceholders)
            <ul class="mt-12 grid grid-cols-2 gap-3 sm:mt-16 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
                @foreach ($images as $image)
                    <li>
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy" decoding="async" class="{{ $tileClasses }}">
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
                @php
                    // Bento layout: with 5+ photos the first one is featured (2x2 on md+, full width on mobile).
                    $featured = $images->count() >= 5;
                    // On the 2-column mobile grid, stretch the last photo across both columns if it would sit alone.
                    $mobileOrphan = ($images->count() - ($featured ? 1 : 0)) % 2 === 1;
                    // Featured layout stays 3 columns on desktop (featured = 4 cells); fill any short last row.
                    $wideRemainder = $featured ? ($images->count() + 3) % 3 : 0;
                @endphp
                <ul @class([
                    'mt-12 grid grid-cols-2 gap-3 sm:mt-16 md:grid-cols-3 md:gap-4',
                    'lg:grid-cols-4' => ! $featured,
                ])>
                    @foreach ($images as $image)
                        @php
                            $isFeatured = $featured && $loop->first;
                            $isOrphan = $mobileOrphan && $loop->last && ! $isFeatured;
                            $cell = match (true) {
                                $isFeatured => 'col-span-2 aspect-[4/3] md:row-span-2 md:aspect-auto',
                                $loop->last && $wideRemainder === 1 => 'col-span-2 aspect-[16/9] md:col-span-3 md:aspect-[21/9]',
                                $loop->last && $wideRemainder === 2 => 'col-span-2 aspect-[16/9] md:aspect-auto',
                                $isOrphan => 'col-span-2 aspect-[16/9] md:col-span-1 md:aspect-[4/3]',
                                default => 'aspect-[4/3]',
                            };
                        @endphp
                        <li class="{{ $cell }}">
                            @if ($image['placeholder'])
                                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy" decoding="async" class="h-full w-full rounded-3xl object-cover shadow-sm">
                            @else
                                <button
                                    type="button"
                                    x-on:click="show({{ $viewIndex++ }}, $el)"
                                    aria-label="View larger photo: {{ $image['alt'] }}"
                                    class="group relative block h-full w-full overflow-hidden rounded-3xl bg-text/5 shadow-sm ring-1 ring-text/10 transition-shadow duration-300 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2"
                                >
                                    <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" @if (! $isFeatured) loading="lazy" @endif decoding="async" class="h-full w-full object-cover transition duration-700 ease-out motion-safe:group-hover:scale-105">
                                    {{-- Caption shade. Decorative copy: the button's aria-label already names the photo.
                                         The featured photo always shows its caption; others reveal it on hover/focus. --}}
                                    <span aria-hidden="true" @class([
                                        'pointer-events-none absolute inset-0 flex items-end bg-gradient-to-t from-black/75 via-black/10 to-transparent text-left transition duration-300',
                                        'p-5 sm:p-6' => $isFeatured,
                                        'p-4 opacity-0 group-hover:opacity-100 group-focus-visible:opacity-100' => ! $isFeatured,
                                    ])>
                                        <span @class([
                                            'line-clamp-2 font-medium text-white',
                                            'text-base sm:text-lg' => $isFeatured,
                                            'text-sm' => ! $isFeatured,
                                        ])>{{ $image['title'] ?? $image['alt'] }}</span>
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
                        x-bind:aria-labelledby="(images[current]?.title || images[current]?.alt) ? 'gallery-caption' : null"
                        x-bind:aria-label="(images[current]?.title || images[current]?.alt) ? null : 'Photo viewer'"
                        class="fixed inset-0 z-[60] flex overflow-y-auto bg-text/95 p-4 dark:bg-background/95"
                        x-on:click.self="close()"
                    >
                        <div class="m-auto flex w-full max-w-4xl flex-col items-center" x-on:click.self="close()">
                            <figure class="flex flex-col items-center">
                                <img x-bind:src="images[current]?.url" x-bind:alt="images[current]?.alt ?? ''" class="max-h-[70dvh] w-auto max-w-full rounded-lg object-contain">
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
    </div>
</section>
