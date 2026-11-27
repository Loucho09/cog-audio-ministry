<x-public-layout
    title="Home"
    description="Learn about the COG Audio Ministry in Dasmariñas and the sound, stage, and livestream roles we serve in."
>
    {{-- Wrapper for the signal line: a 1px line in the left gutter (lg+) with a node at each section after the hero. --}}
    <div class="signal-wrap relative">
        <div aria-hidden="true" data-fx="signal-line" class="signal-line pointer-events-none absolute inset-y-0 left-4 z-10 hidden w-px bg-text/10 lg:block"></div>

        @php
            // Sunday service count from the same schedule the services section shows (0 hides the panel line).
            $sundayServices = collect($schedule['weekly'] ?? [])->where('day', 'Sunday')->sum(fn ($line) => count($line['times'] ?? []));
        @endphp
        <x-home.hero :sunday-services="$sundayServices" />
        <x-home.marquee />
        <x-home.about :services-per-week="$servicesPerWeek" :churches-count="$churches->count()" />
        <x-home.what-we-do />
        <x-home.services :branch="$branch" :schedule="$schedule" />
        <x-home.churches :churches="$churches" />
        <x-home.gallery :images="$galleryImages" />
        <x-home.team :members="$teamMembers" />
        <x-home.join />
        <x-home.contact />
    </div>
    {{-- Not used on the homepage any more (files kept on disk): home/impact (now in About), home/stay-updated (now in Contact). --}}
</x-public-layout>
