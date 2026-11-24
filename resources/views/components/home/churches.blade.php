{{-- Our churches: one team serving every COG church. Names only; addresses and times are TODO. --}}
@php($churches = config('ministry.churches', []))

<section id="churches" aria-labelledby="churches-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
    <x-site.section-backdrop variant="spotlight-left" />
    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center lg:gap-16">
            <div>
                <x-site.section-header
                    id="churches-title"
                    eyebrow="Our Churches"
                    title="One sound, all our churches"
                    align="left"
                    lead="The same heart for worship, shared across every church we serve."
                />

                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach ($churches as $church)
                        <li class="flex items-center justify-between gap-3 rounded-2xl border border-text/10 bg-surface px-4 py-3 shadow-sm">
                            <span class="font-semibold text-text">{{ $church['name'] }}</span>
                            @if ($church['is_main'] ?? false)
                                <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">Main church</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-3xl border border-text/10 bg-surface p-6 shadow-sm sm:p-8">
                <img
                    src="{{ asset('images/cog-church-logo-256.png') }}"
                    alt="Church of God logo: We Exalt You Jesus"
                    width="256"
                    height="256"
                    loading="lazy"
                    class="mx-auto h-28 w-28 rounded-2xl object-cover sm:h-32 sm:w-32"
                >
                <h3 class="mt-6 text-center text-lg font-bold text-text">Behind the sound</h3>
                <p class="mt-3 text-base leading-relaxed text-text/70">
                    Before every service, the Audio team does a sound check, balances the mix, and makes sure the microphones and speakers are ready. During worship, we keep watch so every sermon and every song is heard clearly, by everyone in the room.
                </p>
                <p class="mt-5 text-center">
                    <a href="#join" class="font-semibold text-primary underline-offset-4 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
                        Want to serve with us? Join the team
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
