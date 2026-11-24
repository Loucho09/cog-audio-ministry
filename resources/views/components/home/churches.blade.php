{{-- Our churches: one team serving every COG church. Names only; addresses and times are TODO.
     Dark photo section in both themes (like the hero), so text uses white directly. --}}
@php($churches = config('ministry.churches', []))

<section id="churches" aria-labelledby="churches-title" class="relative isolate overflow-hidden bg-black py-20 sm:py-24 lg:py-32">
    {{-- Church logo as a full-bleed background. The role="img" wrapper carries the accessible name; the image itself is decorative. --}}
    <div role="img" aria-label="Church of God logo: We Exalt You Jesus" class="absolute inset-0 -z-10">
        <img
            src="{{ asset('images/cog-church-logo.png') }}"
            alt=""
            aria-hidden="true"
            width="960"
            height="960"
            loading="lazy"
            decoding="async"
            class="h-full w-full object-cover object-center"
        >
    </div>
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-r from-black/90 via-black/75 to-black/60"></div>
    <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-t from-black/70 via-transparent to-black/40"></div>

    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-white/90">Our Churches</p>
                <h2 id="churches-title" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">One sound, all our churches</h2>
                <p class="mt-4 max-w-xl text-base leading-relaxed text-white/85 sm:text-lg">The same heart for worship, shared across every church we serve.</p>

                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach ($churches as $church)
                        <li class="flex flex-col gap-2 rounded-2xl border border-white/15 bg-black/55 px-4 py-3 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold text-white">{{ $church['name'] }}</span>
                                @if ($church['is_main'] ?? false)
                                    <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-white">Main church</span>
                                @endif
                            </div>
                            @if (filled($church['facebook_url'] ?? null))
                                <a
                                    href="{{ $church['facebook_url'] }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label="{{ $church['name'] }} on Facebook (opens in a new tab)"
                                    class="inline-flex w-fit items-center gap-1.5 text-sm font-semibold text-white underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
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

            <div class="rounded-3xl border border-white/15 bg-black/60 p-6 backdrop-blur-sm sm:p-8">
                <h3 class="text-center text-lg font-bold text-white">Behind the sound</h3>
                <p class="mt-3 text-base leading-relaxed text-white/85">
                    Before every service, the Audio team does a sound check, balances the mix, and makes sure the microphones and speakers are ready. During worship, we keep watch so every sermon and every song is heard clearly, by everyone in the room.
                </p>
                <p class="mt-5 text-center">
                    <a href="#join" class="font-semibold text-white underline underline-offset-4 hover:text-white/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white">
                        Want to serve with us? Join the team
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
