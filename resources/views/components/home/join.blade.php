{{-- Join band: dark console in both themes. Left aligned; the LED panel on the right is decoration
     (the motion layer later lets it follow the pointer through --lx / --ly). --}}
<x-section id="join" tone="console" data-reveal>
    <div class="grid items-center gap-12 lg:grid-cols-12 lg:gap-8">
        <div class="lg:col-span-7">
            <div class="flex items-center gap-4 text-white/60">
                <p class="t-label">Join</p>
                <span aria-hidden="true" class="h-px w-10 bg-current opacity-40"></span>
            </div>

            {{-- TODO: owner to confirm this copy. --}}
            <h2 id="join-title" class="t-h2 mt-6 font-semibold">Serve with us.</h2>
            <p class="t-lead mt-6 max-w-xl text-white/70">
                Do you love serving through sound, stage, or livestream? You do not need to be an expert. We will gladly teach and walk with you. Halina at sumama sa amin!
            </p>

            <div class="mt-10">
                <x-button :href="route('join.create')" variant="on-dark">Apply to join</x-button>
            </div>
        </div>

        <div aria-hidden="true" data-fx="led" class="lg:col-span-4 lg:col-start-9">
            <div class="rounded-console border border-white/15 p-6">
                <div class="flex items-center justify-between font-mono text-[11px] uppercase tracking-[0.12em] text-white/50">
                    <span>Signal</span>
                    <span>Ready</span>
                </div>
                <div class="mt-8 flex items-center gap-4">
                    <span class="led-core h-4 w-4 rounded-full bg-signal ring-4 ring-signal/25"></span>
                    <span class="h-px flex-1 bg-white/15"></span>
                </div>
                <div class="mt-6 flex gap-1.5">
                    @foreach (range(1, 12) as $tick)
                        <span data-led-tick class="h-6 flex-1 rounded-sm {{ $tick <= 7 ? 'bg-white/30' : 'bg-white/10' }}"></span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-section>
