<section id="what-we-do" aria-labelledby="what-we-do-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
    <x-site.section-backdrop variant="rings" fade-from="surface" />
    <div class="mx-auto max-w-6xl px-5 sm:px-6 lg:px-8">
        <x-site.section-header
            id="what-we-do-title"
            eyebrow="How we serve"
            title="What We Do"
            lead="Every service needs a team. These are the main roles we serve in."
        />

        <div class="mx-auto mt-6 max-w-xs" aria-hidden="true">
            <x-site.wave-divider gradient-id="what-we-do-wave" />
        </div>

        @php
            // Icon markup is static SVG paths defined here, never user input.
            $roles = [
                [
                    'title' => 'FOH (Front of House)',
                    'description' => 'FOH mixes the sound the congregation hears, balancing vocals and instruments so every word and song comes through clearly.',
                    'icon' => '<path stroke-linecap="round" d="M6 4v16M12 4v16M18 4v16M4 9h4M10 15h4M16 8h4" />',
                ],
                [
                    'title' => 'Monitors',
                    'description' => 'The monitor mix is what singers and musicians hear on stage, so they can hear themselves and each other while they serve.',
                    'icon' => '<rect x="5.5" y="3" width="13" height="18" rx="2" /><circle cx="12" cy="14" r="3.5" /><circle cx="12" cy="7.5" r="1.25" />',
                ],
                [
                    'title' => 'Livestream',
                    'description' => 'The livestream team shares our services online, with clear sound and steady video for those who join from home.',
                    'icon' => '<rect x="3" y="6.5" width="13" height="11" rx="2" /><path stroke-linejoin="round" d="M16 10.5l5-3v9l-5-3" />',
                ],
                [
                    'title' => 'Stage Tech',
                    'description' => 'Stage tech sets up and cares for microphones, cables, and instruments so the stage is ready before each service begins.',
                    'icon' => '<rect x="9" y="3" width="6" height="11" rx="3" /><path stroke-linecap="round" d="M6 11a6 6 0 0012 0M12 17v4" />',
                ],
            ];
        @endphp

        <ul class="mt-12 grid gap-5 sm:mt-16 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
            @foreach ($roles as $index => $role)
                <li class="group flex flex-col rounded-3xl border border-text/10 bg-surface p-6 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl motion-reduce:transform-none sm:p-8 dark:hover:shadow-[0_0_40px_-8px] dark:hover:shadow-primary/20">
                    <div class="flex items-start justify-between">
                        {{-- Glowing icon tile: gradient from primary to accent at low strength, with a ring. --}}
                        <span aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-accent/15 text-primary-dark ring-1 ring-inset ring-primary/20 transition duration-300 motion-safe:group-hover:scale-105">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                {!! $role['icon'] !!}
                            </svg>
                        </span>
                        <span aria-hidden="true" class="font-mono text-xs font-semibold tracking-wider text-text/70">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>

                    <h3 class="mt-6 text-lg font-bold text-text">{{ $role['title'] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-text/75">
                        {{ $role['description'] }}
                    </p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
