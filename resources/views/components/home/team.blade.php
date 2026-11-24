@props(['members' => collect()])

@php
    $initials = fn (string $name) => \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('');
@endphp

@if (config('ministry.show_team') && collect($members)->isNotEmpty())
    <section id="team" aria-labelledby="team-title" class="bg-background py-16 sm:py-20">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <h2 id="team-title" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">Meet the Team</h2>
            </div>

            <ul class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($members as $member)
                    <li class="rounded-2xl border border-text/10 bg-surface p-6 text-center">
                        @if ($member->photo_url)
                            <img src="{{ $member->photo_url }}" alt="{{ $member->name }}" width="128" height="128" loading="lazy" decoding="async" class="mx-auto h-32 w-32 rounded-full object-cover">
                        @else
                            <span aria-hidden="true" class="mx-auto flex h-32 w-32 items-center justify-center rounded-full bg-primary-dark text-3xl font-semibold text-surface">{{ $initials($member->name) }}</span>
                        @endif
                        <h3 class="mt-4 text-lg font-semibold text-text">{{ $member->name }}</h3>
                        @if ($member->role_title)
                            <p class="mt-1 text-sm text-text/70">{{ $member->role_title }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
