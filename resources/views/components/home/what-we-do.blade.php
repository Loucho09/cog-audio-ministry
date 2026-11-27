{{-- CH 02. Generic role descriptions only; nothing here claims facts about this ministry. --}}
@php
    $lead = [
        'n' => '01',
        'title' => 'Front of House',
        'icon' => 'adjustments-horizontal',
        'text' => 'Mixes the sound the congregation hears. Vocals and instruments are balanced so every word and every song comes through clearly.',
    ];
    $roles = [
        ['n' => '02', 'title' => 'Monitors', 'icon' => 'headphones', 'text' => 'Mixes what singers and musicians hear on stage, so they can hear themselves and each other while they serve.'],
        ['n' => '03', 'title' => 'Broadcast / Livestream', 'icon' => 'broadcast', 'text' => 'Shares the service with those at home, with clear sound and steady video.'],
        ['n' => '04', 'title' => 'Recording', 'icon' => 'microphone', 'text' => 'Captures the service so messages and songs can be kept and shared later.'],
        ['n' => '05', 'title' => 'Stage & Patching', 'icon' => 'plug-connected', 'text' => 'Sets up and cares for microphones, cables, and instruments before each service.'],
    ];
@endphp

<x-section id="what-we-do" tone="surface">
    <div data-fx="fader-heading">
        <x-section-heading
            id="what-we-do"
            channel="02"
            eyebrow="What we do"
            title="Five roles, one sound."
            description="Every service needs a team. These are the places you can serve."
        />
    </div>

    <div class="mt-12 grid gap-6 lg:mt-16 lg:grid-cols-12 lg:gap-8" data-fx="patch-rows">
        <article data-patch-row class="fx-spot relative flex flex-col justify-between rounded-console border border-text/10 bg-background p-6 transition-colors hover:border-primary/40 lg:col-span-5 lg:p-10">
            <div class="flex items-start justify-between gap-4">
                <x-tabler-icon :name="$lead['icon']" class="h-12 w-12 text-primary-dark dark:text-primary" />
                <p class="t-label text-text/60">CH {{ $lead['n'] }}</p>
            </div>
            <div class="mt-16 lg:mt-24">
                <h3 class="text-3xl font-semibold tracking-tight lg:text-4xl">{{ $lead['title'] }}</h3>
                <p class="mt-4 max-w-md leading-relaxed text-text/70">{{ $lead['text'] }}</p>
            </div>
        </article>

        <ul class="divide-y divide-text/10 border-y border-text/10 lg:col-span-7">
            @foreach ($roles as $role)
                <li data-patch-row class="fx-spot relative grid grid-cols-[auto_1fr] gap-x-5 gap-y-1 py-6 sm:grid-cols-[auto_auto_1fr] sm:items-start sm:gap-x-6 lg:py-7">
                    <p class="t-label pt-1 text-text/60">{{ $role['n'] }}</p>
                    <x-tabler-icon :name="$role['icon']" class="hidden h-7 w-7 text-primary-dark sm:block dark:text-primary" />
                    <div class="col-start-2 sm:col-start-3">
                        <h3 class="text-xl font-semibold tracking-tight">{{ $role['title'] }}</h3>
                        <p class="mt-2 max-w-xl leading-relaxed text-text/70">{{ $role['text'] }}</p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</x-section>
