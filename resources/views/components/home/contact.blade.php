@php
    $email = config('ministry.email');
    $facebook = config('ministry.facebook_url');
    $messenger = config('ministry.messenger_url');
    $address = config('ministry.address');
    $hasContact = collect([$email, $facebook, $messenger, $address])->filter()->isNotEmpty();

    // Icon paths are static SVG markup defined here, never user input.
    $iconClasses = 'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-accent/15 text-primary-dark ring-1 ring-inset ring-primary/20 transition duration-300 motion-safe:group-hover:scale-105';
    $cardClasses = 'group flex min-h-11 items-center gap-4 rounded-3xl border border-text/15 bg-surface p-5 dark:bg-background shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg motion-reduce:transform-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark sm:p-6 dark:hover:shadow-[0_0_40px_-8px] dark:hover:shadow-primary/20';
@endphp

<section id="contact" aria-labelledby="contact-title" class="relative isolate overflow-hidden bg-surface py-20 sm:py-24 lg:py-32">
    <x-site.section-backdrop variant="floor" fade-from="background" />
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-12 lg:items-start lg:gap-16">
            {{-- Desktop: heading in the left column, contact cards and map on the right. --}}
            <div class="lg:col-span-4">
                <x-site.section-header id="contact-title" eyebrow="Contact" title="Contact Us" lead="We would love to hear from you." align="split" />
            </div>

        <div class="mt-12 grid grid-cols-1 gap-8 sm:mt-16 lg:col-span-8 lg:mt-0 {{ $address ? 'lg:grid-cols-2 lg:items-stretch lg:gap-10' : '' }}">
            @if ($hasContact)
            <ul class="grid w-full grid-cols-1 gap-4 {{ $address ? '' : 'mx-auto max-w-xl lg:mx-0 lg:max-w-none xl:grid-cols-2' }}">
                @if ($email)
                    <li>
                        <a href="mailto:{{ $email }}" class="{{ $cardClasses }}">
                            <span aria-hidden="true" class="{{ $iconClasses }}">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-text/80">Email us</span>
                                {{-- <wbr> after "@" lets a long address wrap at a clean point on small phones. The email is escaped first. --}}
                                <span class="mt-1 block break-words text-sm font-semibold text-text sm:text-base">{!! str_replace('@', '@<wbr>', e($email)) !!}</span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-text/40 transition duration-300 motion-safe:group-hover:translate-x-0.5 group-hover:text-primary-dark" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                        </a>
                    </li>
                @endif

                @if ($facebook)
                    <li>
                        <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="{{ $cardClasses }}">
                            <span aria-hidden="true" class="{{ $iconClasses }}">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-text/80">Follow our page</span>
                                <span class="mt-1 block font-semibold text-text">Facebook<span class="sr-only"> (opens in a new tab)</span></span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-text/40 transition duration-300 motion-safe:group-hover:translate-x-0.5 group-hover:text-primary-dark" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                        </a>
                    </li>
                @endif

                @if ($messenger)
                    <li>
                        <a href="{{ $messenger }}" target="_blank" rel="noopener noreferrer" class="{{ $cardClasses }}">
                            <span aria-hidden="true" class="{{ $iconClasses }}">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-text/80">Chat with us</span>
                                <span class="mt-1 block font-semibold text-text">Messenger<span class="sr-only"> (opens in a new tab)</span></span>
                            </span>
                            <svg class="h-5 w-5 shrink-0 text-text/40 transition duration-300 motion-safe:group-hover:translate-x-0.5 group-hover:text-primary-dark" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" />
                            </svg>
                        </a>
                    </li>
                @endif

                @if ($address)
                    <li class="flex min-h-11 items-center gap-4 rounded-3xl border border-text/15 bg-surface p-5 dark:bg-background shadow-sm sm:p-6">
                        <span aria-hidden="true" class="{{ $iconClasses }}">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                        </span>
                        <address class="min-w-0 flex-1 not-italic">
                            <span class="block text-xs font-semibold uppercase tracking-[0.18em] text-text/80">Visit us</span>
                            <span class="mt-1 block break-words font-semibold text-text">{{ $address }}</span>
                        </address>
                    </li>
                @endif
            </ul>
            @else
                <p class="mx-auto max-w-2xl text-center text-base text-text/80">Contact details are coming soon.</p>
            @endif

            @if ($address)
                <iframe
                    src="https://maps.google.com/maps?q={{ urlencode($address) }}&amp;output=embed"
                    title="Map showing {{ $address }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="h-72 w-full rounded-3xl border border-text/15 shadow-sm lg:h-auto lg:min-h-80"
                ></iframe>
            @endif
        </div>
        </div>
    </div>
</section>
