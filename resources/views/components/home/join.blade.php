<section id="join" aria-labelledby="join-title" class="relative isolate overflow-hidden bg-primary-dark py-20 sm:py-24 lg:py-32">
    {{-- Light mode: a bold deep-blue band (primary-dark, white text passes AA). Dark mode: near-black stage band with glows.
         Both get crisp white sound-wave rings (.backdrop-waves). Text is white in both themes. Decorative layers are aria-hidden. --}}
    <div aria-hidden="true" class="absolute inset-0 -z-20 hidden bg-black dark:block"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-0 -z-10"><div class="backdrop-waves absolute inset-0"></div></div>
    <x-site.glow tone="primary" class="-left-32 -top-32 hidden h-96 w-96 opacity-60 dark:block" />

    {{-- Soft fades into the neighbouring sections, plus an accent hairline on the top edge. --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-0 hidden h-24 bg-gradient-to-b from-background to-transparent dark:block"></div>
    <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 bottom-0 hidden h-24 bg-gradient-to-t from-background to-transparent dark:block"></div>
    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-accent to-transparent"></span>
    <x-site.glow tone="accent" class="-bottom-40 -right-24 hidden h-[28rem] w-[28rem] opacity-50 dark:block" />

    <div class="relative mx-auto flex max-w-3xl flex-col items-center px-5 text-center sm:px-6 lg:px-8">
        {{-- White bars on the blue band (light mode); the usual blue-to-red bars in dark mode. --}}
        <x-site.eq-bars class="mb-6 [&>span]:from-white [&>span]:to-white/70 dark:[&>span]:from-primary dark:[&>span]:to-accent" />

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
            {{-- Light mode: white button on the blue band. Dark mode: the normal blue button. --}}
            <x-site.button size="lg" href="#contact" class="!bg-surface !text-primary-dark shadow-black/20 focus-visible:!ring-white focus-visible:!ring-offset-primary-dark dark:!bg-primary-dark dark:!text-surface dark:focus-visible:!ring-offset-black">Message us to join</x-site.button>
        </div>
    </div>
</section>
