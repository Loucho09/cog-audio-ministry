@php
    $email = config('ministry.email');
    $facebook = config('ministry.facebook_url');
    $messenger = config('ministry.messenger_url');
    $address = config('ministry.address');
    $linkClasses = 'inline-flex min-h-11 items-center rounded-sm font-medium text-primary-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark';
@endphp

<section id="contact" aria-labelledby="contact-title" class="bg-surface py-16 sm:py-20">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 id="contact-title" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Contact Us</h2>
            <p class="mt-4 text-base leading-relaxed text-text/70">We would love to hear from you.</p>
        </div>

        <div class="mt-12 grid gap-8 {{ $address ? 'lg:grid-cols-2' : '' }}">
            <ul class="mx-auto w-full max-w-md space-y-2 text-center {{ $address ? 'lg:mx-0 lg:text-left' : '' }}">
                @if ($email)
                    <li><a href="mailto:{{ $email }}" class="{{ $linkClasses }}">{{ $email }}</a></li>
                @endif
                @if ($facebook)
                    <li><a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="{{ $linkClasses }}">Facebook</a></li>
                @endif
                @if ($messenger)
                    <li><a href="{{ $messenger }}" target="_blank" rel="noopener noreferrer" class="{{ $linkClasses }}">Messenger</a></li>
                @endif
                @if ($address)
                    <li><address class="py-2 not-italic text-text">{{ $address }}</address></li>
                @endif
            </ul>

            @if ($address)
                <iframe
                    src="https://maps.google.com/maps?q={{ urlencode($address) }}&amp;output=embed"
                    title="Map showing {{ $address }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="h-72 w-full rounded-2xl border border-text/10"
                ></iframe>
            @endif
        </div>
    </div>
</section>
