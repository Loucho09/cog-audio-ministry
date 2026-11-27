{{-- CH 07. Contact and "stay updated" in one place. Anything not set in config/ministry.php shows a plain
     "to be added" note, so nothing here is invented. The map only renders when an address is set. --}}
@php
    $email = config('ministry.email');
    $facebook = config('ministry.facebook_url');
    $messenger = config('ministry.messenger_url');
    $address = config('ministry.address');
    $rowClasses = 'group flex min-h-12 items-center justify-between gap-4 py-4 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal';
@endphp

<x-section id="contact" tone="surface">
    <div data-fx="fader-heading">
        <x-section-heading
            id="contact"
            channel="07"
            eyebrow="Contact"
            title="Say hello."
            description="Follow us for service updates and ministry news, or write to us any time."
        />
    </div>

    <div class="mt-12 grid gap-10 lg:mt-16 lg:grid-cols-12 lg:gap-8">
        <div class="lg:col-span-7">
            <p class="t-label text-text/60">Email</p>
            @if ($email)
                {{-- <wbr> after "@" lets a long address wrap at a clean point on small phones. The email is escaped first. --}}
                <a href="mailto:{{ $email }}" class="link-fader mt-4 block break-words font-mono text-xl font-medium tracking-tight focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal sm:text-3xl lg:text-4xl">{!! str_replace('@', '@<wbr>', e($email)) !!}</a>
            @else
                {{-- TODO: set MINISTRY_EMAIL in config/ministry.php. --}}
                <p class="mt-4 font-mono text-lg text-text/50">Email to be added.</p>
            @endif
        </div>

        <ul class="divide-y divide-text/10 border-y border-text/10 lg:col-span-4 lg:col-start-9">
            <li>
                @if ($facebook)
                    <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" aria-label="Follow us on Facebook (opens in a new tab)" class="{{ $rowClasses }}">
                        <span class="flex items-center gap-3"><x-tabler-icon name="brand-facebook" class="h-5 w-5 text-primary-dark dark:text-primary" /><span>Facebook</span></span>
                        <x-tabler-icon name="arrow-right" class="h-4 w-4 text-text/40 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none" />
                    </a>
                @else
                    {{-- TODO: set the Facebook page URL in config/ministry.php. --}}
                    <p class="{{ $rowClasses }} text-text/50"><span class="flex items-center gap-3"><x-tabler-icon name="brand-facebook" class="h-5 w-5" /><span class="font-mono text-sm">Facebook page to be added</span></span></p>
                @endif
            </li>
            <li>
                @if ($messenger)
                    <a href="{{ $messenger }}" target="_blank" rel="noopener noreferrer" aria-label="Message us on Messenger (opens in a new tab)" class="{{ $rowClasses }}">
                        <span class="flex items-center gap-3"><x-tabler-icon name="message-circle" class="h-5 w-5 text-primary-dark dark:text-primary" /><span>Messenger</span></span>
                        <x-tabler-icon name="arrow-right" class="h-4 w-4 text-text/40 transition-transform group-hover:translate-x-0.5 motion-reduce:transition-none" />
                    </a>
                @else
                    {{-- TODO: owner to add the Messenger link (config/ministry.php). --}}
                    <p class="{{ $rowClasses }} text-text/50"><span class="flex items-center gap-3"><x-tabler-icon name="message-circle" class="h-5 w-5" /><span class="font-mono text-sm">Chat link to be added</span></span></p>
                @endif
            </li>
            <li>
                <address class="{{ $rowClasses }} items-start not-italic {{ $address ? '' : 'text-text/50' }}">
                    <span class="flex items-start gap-3">
                        <x-tabler-icon name="map-pin" class="mt-0.5 h-5 w-5 {{ $address ? 'text-primary-dark dark:text-primary' : '' }}" />
                        @if ($address)
                            <span class="min-w-0 break-words">{{ $address }}</span>
                        @else
                            {{-- TODO: owner to add the church address (config/ministry.php). --}}
                            <span class="font-mono text-sm">Address to be added</span>
                        @endif
                    </span>
                </address>
            </li>
        </ul>
    </div>

    @if ($address)
        <iframe
            src="https://maps.google.com/maps?q={{ urlencode($address) }}&amp;output=embed"
            title="Map showing {{ $address }}"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            class="mt-12 h-72 w-full rounded-console border border-text/10 lg:mt-16 lg:h-96"
        ></iframe>
    @endif
</x-section>
