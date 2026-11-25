{{-- Our churches: one team serving every COG church. Names only; addresses and times are TODO.
     Light mode: soft watermark of the church logo behind dark text. Dark mode: the logo as a dark photo band.
     Text uses theme tokens, so it stays readable in both. --}}
@props(['churches' => collect()])

<section id="churches" data-reveal aria-labelledby="churches-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
    {{-- Church logo as a full-bleed background. The role="img" wrapper carries the accessible name; the image itself is decorative. --}}
    <div role="img" aria-label="Church of God logo: We Exalt You Jesus" class="absolute inset-0 -z-10">
        {{-- Opacity sits on the wrapper so the img's own fade-in opacity is not overridden. --}}
        <x-skeleton-image
            src="{{ asset('images/cog-church-logo.png') }}"
            alt=""
            aria-hidden="true"
            width="960"
            height="960"
            class="h-full w-full opacity-40 dark:opacity-100"
            img-class="object-center"
        />
    </div>
    {{-- Light mode: a background-coloured veil keeps the logo a faint watermark. --}}
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-r from-background via-background/85 to-background/55 dark:hidden"></div>
    {{-- Dark mode: the original dark photo treatment. --}}
    <div aria-hidden="true" class="absolute inset-0 -z-10 hidden bg-gradient-to-r from-black/90 via-black/75 to-black/60 dark:block"></div>
    <div aria-hidden="true" class="absolute inset-0 -z-10 hidden bg-gradient-to-t from-black/70 via-transparent to-black/40 dark:block"></div>

    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
            <div>
                <p class="inline-flex items-center gap-3 text-xs font-semibold uppercase tracking-[0.2em] text-accent-text dark:text-white/90"><span aria-hidden="true" class="h-0.5 w-8 rounded-full bg-accent"></span>Our Churches</p>
                <h2 id="churches-title" class="mt-3 text-3xl font-extrabold tracking-tight text-text sm:text-4xl">One sound, all our churches</h2>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-text/80 sm:text-lg">The same heart for worship, shared across every church we serve.</p>

                <ul class="mt-8 grid grid-cols-2 gap-3 [&>li:last-child:nth-child(odd)]:col-span-2">
                    @foreach ($churches as $church)
                        <li class="flex flex-col gap-2 rounded-2xl border border-text/15 bg-surface px-3 py-3 shadow-sm sm:px-4 dark:border-white/15 dark:bg-black/55 dark:shadow-none dark:backdrop-blur-sm">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <span class="min-w-0 break-words font-semibold text-text">{{ $church->name }}</span>
                                @if ($church->is_main)
                                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary-dark dark:bg-white/15 dark:text-white">Main church</span>
                                @endif
                            </div>
                            @if ($church->safeFacebookUrl())
                                <a
                                    href="{{ $church->safeFacebookUrl() }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="{{ $church->name }} on Facebook (opens in a new tab)"
                                    class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-primary-dark underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-dark dark:text-white dark:focus-visible:outline-white"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M13.5 21v-7.5h2.6l.4-3h-3V8.6c0-.9.3-1.5 1.5-1.5h1.6V4.4c-.3 0-1.2-.1-2.3-.1-2.3 0-3.8 1.4-3.8 3.9v2.3H7.9v3h2.6V21h3z" />
                                    </svg>
                                    Facebook
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-3xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8 dark:border-white/15 dark:bg-black/60 dark:shadow-none dark:backdrop-blur-sm">
                <h3 class="text-center text-lg font-bold text-text">Behind the sound</h3>
                <p class="mt-3 text-base leading-relaxed text-text/80">
                    Before every service, the Audio team does a sound check, balances the mix, and makes sure the microphones and speakers are ready. During worship, we keep watch so every sermon and every song is heard clearly, by everyone in the room.
                </p>
                <p class="mt-5 text-center">
                    <a href="#join" class="font-semibold text-primary-dark underline underline-offset-4 hover:text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-dark dark:text-white dark:hover:text-white/80 dark:focus-visible:outline-white">
                        Want to serve with us? Join the team
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
