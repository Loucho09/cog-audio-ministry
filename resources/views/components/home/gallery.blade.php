@props(['images' => collect()])

@php
    $images = collect($images)->values();
    $allPlaceholders = $images->isNotEmpty() && $images->every(fn ($i) => $i['placeholder']);
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
                    images: @js($images),
                    open: false,
                    current: 0,
                    trigger: null,
                    show(i, el) {
                        this.current = i;
                        this.trigger = el;
                        this.open = true;
                        document.body.classList.add('overflow-hidden');
                        this.$nextTick(() => this.$refs.close.focus());
                    },
                    close() {
                        this.open = false;
                        document.body.classList.remove('overflow-hidden');
                        this.$nextTick(() => this.trigger && this.trigger.focus());
                    },
                    next() { this.current = (this.current + 1) % this.images.length; },
                    prev() { this.current = (this.current - 1 + this.images.length) % this.images.length; },
                }"
                x-on:keydown.escape.window="open && close()"
                x-on:keydown.arrow-right.window="open && next()"
                x-on:keydown.arrow-left.window="open && prev()"
            >
                <ul class="mt-12 grid grid-cols-2 gap-3 md:grid-cols-3 md:gap-4 lg:grid-cols-4">
                    @foreach ($images as $index => $image)
                        <li>
                            <button
                                type="button"
                                x-on:click="show({{ $index }}, $el)"
                                aria-label="View larger photo: {{ $image['alt'] }}"
                                class="block w-full rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2"
                            >
                                <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" loading="lazy" decoding="async" class="aspect-[4/3] w-full rounded-lg object-cover">
                            </button>
                        </li>
                    @endforeach
                </ul>

                <div
                    x-show="open"
                    x-cloak
                    role="dialog"
                    aria-modal="true"
                    aria-label="Photo gallery viewer"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-text/90 p-4"
                    x-on:click.self="close()"
                >
                    <figure class="flex max-h-full max-w-4xl flex-col items-center" x-on:click.self="close()">
                        <img x-bind:src="images[current]?.url" x-bind:alt="images[current]?.alt" class="max-h-[75vh] w-auto max-w-full rounded-lg object-contain">
                        <figcaption class="mt-3 text-center text-sm text-surface" x-text="images[current]?.title || images[current]?.alt"></figcaption>
                    </figure>

                    <button type="button" x-ref="close" x-on:click="close()" aria-label="Close" class="absolute right-4 top-4 rounded-full bg-surface p-2 text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                    <button type="button" x-on:click="prev()" aria-label="Previous photo" class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-surface p-2 text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2 sm:left-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button type="button" x-on:click="next()" aria-label="Next photo" class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-surface p-2 text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark focus-visible:ring-offset-2 sm:right-4">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                </div>
            </div>
        @endif
    </div>
</section>
