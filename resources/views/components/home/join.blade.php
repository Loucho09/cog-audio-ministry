<section id="join" aria-labelledby="join-title" class="relative isolate overflow-hidden bg-black py-20 sm:py-24 lg:py-32">
    {{-- Stage band: near-black in both themes so white text always passes AA. Glows are decorative. --}}
    <x-site.glow tone="primary" class="-left-32 -top-32 h-96 w-96 opacity-60" />
    <x-site.glow tone="accent" class="-bottom-40 -right-24 h-[28rem] w-[28rem] opacity-50" />

    <div class="relative mx-auto flex max-w-3xl flex-col items-center px-5 text-center sm:px-6 lg:px-8">
        <x-site.eq-bars class="mb-6" />

        {{-- TODO: owner to confirm this copy. --}}
        <x-site.section-header
            id="join-title"
            tone="stage"
            eyebrow="Join the team"
            title="Join Us"
            lead="Do you love serving through sound, stage, or livestream? You do not need to be an expert. We will gladly teach and walk with you. Halina at sumama sa amin!"
        />

        <div class="mt-10">
            {{-- Points to #contact for now; it will link to the Phase 3 application form later. --}}
            <x-site.button size="lg" href="#contact" class="focus-visible:!ring-white focus-visible:!ring-offset-black">Message us to join</x-site.button>
        </div>
    </div>
</section>
