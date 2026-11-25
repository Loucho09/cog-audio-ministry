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
    <section id="team" aria-labelledby="team-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
        <x-site.section-backdrop variant="spotlight-left" :mirror="true" />
        <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
            <x-site.section-header id="team-title" eyebrow="Our team" title="Meet the Team" />

            <ul class="mt-12 grid grid-cols-1 gap-5 sm:mt-16 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
                @foreach ($members as $member)
                    <li class="group rounded-3xl border border-text/15 bg-surface p-6 text-center shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl motion-reduce:transform-none sm:p-8 dark:hover:shadow-[0_0_40px_-8px] dark:hover:shadow-primary/20">
                        {{-- Gradient ring around the photo (or initials). --}}
                        <div class="mx-auto flex h-36 w-36 items-center justify-center rounded-full bg-gradient-to-br from-primary to-accent p-[3px] transition duration-300 motion-safe:group-hover:rotate-3">
                            @if ($member->photo_url)
                                <x-skeleton-image src="{{ $member->photo_url }}" alt="{{ $member->name }}" width="128" height="128" rounded="rounded-full" class="h-full w-full" img-class="rounded-full border-4 border-surface"/>
                            @else
                                <span aria-hidden="true" class="flex h-full w-full items-center justify-center rounded-full border-4 border-surface bg-background text-3xl font-extrabold tracking-tight text-text">{{ $initials($member->name) }}</span>
                            @endif
                        </div>
                        <h3 class="mt-5 text-lg font-bold text-text break-words">{{ $member->name }}</h3>
                        @if ($member->role_title)
                            <p class="mt-1 text-sm text-text/80">{{ $member->role_title }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
