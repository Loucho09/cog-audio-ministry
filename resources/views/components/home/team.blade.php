{{-- CH 06. Behind the show_team flag (config/ministry.php). Members arrive full-time first from the controller.
     With the flag on and nobody featured yet, three clearly labelled placeholders show instead. --}}
@props(['members' => collect()])

@php
    $initials = fn (string $name) => \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
    $members = collect($members);
@endphp

@if (config('ministry.show_team'))
    <x-section id="team" tone="surface">
        <div data-fx="fader-heading">
            <x-section-heading
                id="team"
                channel="06"
                eyebrow="The team"
                title="Meet the team."
                description="The people who keep every service clear."
            />
        </div>

        <ul class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:mt-16 lg:grid-cols-4 lg:gap-8" data-fx="cascade">
            @forelse ($members as $member)
                <li data-cascade-item>
                    <x-card class="gap-4">
                        <div class="photo-tone aspect-square w-full overflow-hidden rounded-console bg-background">
                            @if ($member->photo_url)
                                <x-skeleton-image src="{{ $member->photo_url }}" alt="{{ $member->name }}" width="400" height="400" class="h-full w-full" img-class="object-cover"/>
                            @else
                                <span aria-hidden="true" class="flex h-full w-full items-center justify-center font-mono text-4xl font-medium text-text/40">{{ $initials($member->name) }}</span>
                            @endif
                        </div>
                        <div>
                            <h3 class="break-words text-lg font-semibold tracking-tight">{{ $member->name }}</h3>
                            @if ($member->role_title)
                                <p class="t-label mt-1 text-text/60">{{ $member->role_title }}</p>
                            @endif
                        </div>
                    </x-card>
                </li>
            @empty
                @foreach (range(1, 3) as $n)
                    {{-- TODO: team member placeholder. Add featured team members in Filament; placeholders disappear automatically. --}}
                    <li data-cascade-item>
                        <x-card class="gap-4 border-dashed">
                            <div class="flex aspect-square w-full items-center justify-center rounded-console bg-background">
                                <span class="font-mono text-xs uppercase tracking-[0.12em] text-text/50">Photo pending</span>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold tracking-tight text-text/70">TODO: team member</h3>
                                <p class="t-label mt-1 text-text/50">Role to be added</p>
                            </div>
                        </x-card>
                    </li>
                @endforeach
            @endforelse
        </ul>
    </x-section>
@endif
