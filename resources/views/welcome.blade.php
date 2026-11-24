<x-public-layout
    title="Home"
    description="Learn about the COG Audio Ministry in Dasmariñas and the sound, stage, and livestream roles we serve in."
>
    <x-home.hero />
    <x-home.impact :services-per-week="$servicesPerWeek" />
    <x-home.about />
    <x-home.what-we-do />
    <x-home.team :members="$teamMembers" />
    <x-home.services :branch="$branch" :schedule="$schedule" />
    <x-home.gallery :images="$galleryImages" />
    <x-home.join />
    <x-home.contact />
</x-public-layout>
