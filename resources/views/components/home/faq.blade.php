@props(['faqs' => collect()])

@if (collect($faqs)->isNotEmpty())
    <section id="faq" aria-labelledby="faq-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
        <x-site.section-backdrop variant="grid" />
        <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
            <div class="grid gap-12 lg:grid-cols-[1fr_1.4fr] lg:items-start lg:gap-16">
                <x-site.section-header id="faq-title" eyebrow="FAQ" title="Frequently Asked Questions" align="left" />

                <div x-data="{ open: null }" class="grid gap-3 sm:gap-4">
                    @foreach ($faqs as $faq)
                        <div
                            class="overflow-hidden rounded-2xl border bg-surface transition-[border-color,box-shadow] duration-200"
                            x-bind:class="open === {{ $faq->id }} ? 'border-primary/40 shadow-md' : 'border-text/15 shadow-sm'"
                        >
                            <h3>
                                <button
                                    type="button"
                                    id="faq-trigger-{{ $faq->id }}"
                                    aria-controls="faq-panel-{{ $faq->id }}"
                                    aria-expanded="false"
                                    x-bind:aria-expanded="(open === {{ $faq->id }}).toString()"
                                    x-on:click="open = open === {{ $faq->id }} ? null : {{ $faq->id }}"
                                    class="flex min-h-11 w-full items-center justify-between gap-4 px-5 py-4 text-left text-base font-semibold text-text transition-colors duration-200 hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-dark sm:px-6 sm:py-5"
                                >
                                    <span class="min-w-0 break-words">{{ $faq->question }}</span>

                                    {{-- Plus that turns into a minus when open (two bars; the vertical one collapses). --}}
                                    <span aria-hidden="true" class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary-dark transition-colors duration-200">
                                        <span class="absolute h-0.5 w-3.5 rounded-full bg-current"></span>
                                        <span
                                            class="absolute h-3.5 w-0.5 rounded-full bg-current motion-safe:transition-transform motion-safe:duration-200"
                                            x-bind:class="open === {{ $faq->id }} ? 'scale-y-0' : ''"
                                        ></span>
                                    </span>
                                </button>
                            </h3>
                            <div
                                id="faq-panel-{{ $faq->id }}"
                                role="region"
                                aria-labelledby="faq-trigger-{{ $faq->id }}"
                                x-show="open === {{ $faq->id }}"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                class="px-5 pb-5 text-base leading-relaxed text-text/80 sm:px-6 sm:pb-6"
                            >
                                {!! nl2br(e($faq->answer)) !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif
