{{-- Stay updated: follow on Facebook / message on Messenger. Hidden when both URLs are empty. --}}
@php
    $facebookUrl = config('ministry.facebook_url');
    $messengerUrl = config('ministry.messenger_url');
@endphp

@if (filled($facebookUrl) || filled($messengerUrl))
    <section id="updates" aria-labelledby="updates-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
        <x-site.section-backdrop variant="vignette" />

        <div class="relative mx-auto max-w-3xl px-5 text-center sm:px-6 lg:px-8">
            <div class="rounded-3xl border border-text/15 bg-surface p-8 shadow-sm sm:p-10">
                <x-site.section-header
                    id="updates-title"
                    eyebrow="Stay Updated"
                    title="Don't miss a Sunday"
                    lead="Follow us for service updates, behind-the-scenes moments, and ministry news."
                />

                <div class="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row sm:items-center">
                    @if (filled($facebookUrl))
                        <x-site.button :href="$facebookUrl" size="lg" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Facebook (opens in a new tab)">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H7.9v3h2.6V21h3z"/></svg>
                            Follow us on Facebook
                        </x-site.button>
                    @endif

                    @if (filled($messengerUrl))
                        <x-site.button :href="$messengerUrl" variant="secondary" size="lg" target="_blank" rel="noopener noreferrer" aria-label="Message us on Messenger (opens in a new tab)">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M21 11.5a8.5 8.5 0 0 1-12.4 7.5L3 20.5l1.6-5A8.5 8.5 0 1 1 21 11.5z"/></svg>
                            Message us on Messenger
                        </x-site.button>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
