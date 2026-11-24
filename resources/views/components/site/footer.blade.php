<footer class="border-t border-text/10 bg-surface">
    <div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 py-12 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:px-8">

        <div class="max-w-sm">
            <p class="text-base font-semibold text-text">{{ config('ministry.name') }}</p>
            <p class="text-sm text-text/60">{{ config('ministry.subheader') }}</p>
            {{-- TODO: owner to confirm this short line --}}
            <p class="mt-3 text-sm leading-relaxed text-text/80">Serving together with heart and harmony.</p>
        </div>

        <nav aria-label="Footer" class="min-w-0">
            <ul class="flex flex-col gap-3 text-sm sm:flex-row sm:flex-wrap sm:gap-x-6">
                @if (config('ministry.facebook_url'))
                    <li>
                        <a
                            href="{{ config('ministry.facebook_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-sm text-text/80 transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                        >Facebook<span class="sr-only"> (opens in a new tab)</span></a>
                    </li>
                @endif

                @if (config('ministry.messenger_url'))
                    <li>
                        <a
                            href="{{ config('ministry.messenger_url') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-sm text-text/80 transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                        >Messenger<span class="sr-only"> (opens in a new tab)</span></a>
                    </li>
                @endif

                @if (config('ministry.email'))
                    <li>
                        <a
                            href="mailto:{{ config('ministry.email') }}"
                            class="break-words rounded-sm text-text/80 transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent"
                        >{{ config('ministry.email') }}</a>
                    </li>
                @endif
            </ul>
        </nav>
    </div>

    <div class="border-t border-text/10">
        <p class="mx-auto max-w-6xl px-4 py-6 text-center text-xs text-text/60 sm:px-6 lg:px-8">
            &copy; {{ now()->year }} {{ config('ministry.name') }}. All rights reserved.
        </p>
    </div>
</footer>
