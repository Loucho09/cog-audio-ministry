<x-public-layout
    title="Home"
    description="Learn about the COG Audio Ministry in Dasmariñas and the sound, stage, and livestream roles we serve in."
>
    <x-home.hero />
    <x-home.about />
    <x-home.what-we-do />
    {{-- TODO: meet the team (behind config show_team) goes here --}}
    <x-home.services :branch="$branch" :schedule="$schedule" />
    <x-home.gallery :images="$galleryImages" />

    {{-- TODO (later slices): join us (#join), FAQ (#faq), contact (#contact), footer is already in the layout --}}
</x-public-layout>
