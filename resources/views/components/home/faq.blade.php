@props(['faqs' => collect()])

@if (collect($faqs)->isNotEmpty())
    <section id="faq" aria-labelledby="faq-title" class="bg-background py-16 sm:py-20">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h2 id="faq-title" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Frequently Asked Questions</h2>
            </div>

            <div x-data="{ open: null }" class="mt-12 divide-y divide-text/10 rounded-2xl border border-text/10 bg-surface">
                @foreach ($faqs as $faq)
                    <div>
                        <h3>
                            <button
                                type="button"
                                id="faq-trigger-{{ $faq->id }}"
                                aria-controls="faq-panel-{{ $faq->id }}"
                                aria-expanded="false"
                                x-bind:aria-expanded="(open === {{ $faq->id }}).toString()"
                                x-on:click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                                class="flex min-h-11 w-full items-center justify-between gap-4 px-5 py-4 text-left text-base font-medium text-text transition-colors duration-200 hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-dark"
                            >
                                <span>{{ $faq->question }}</span>
                                <svg class="h-5 w-5 shrink-0 motion-safe:transition-transform motion-safe:duration-200" x-bind:class="open === {{ $faq->id }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>
                        </h3>
                        <div
                            id="faq-panel-{{ $faq->id }}"
                            role="region"
                            aria-labelledby="faq-trigger-{{ $faq->id }}"
                            x-show="open === {{ $faq->id }}"
                            x-cloak
                            class="px-5 pb-5 text-base leading-relaxed text-text/80"
                        >
                            {!! nl2br(e($faq->answer)) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
