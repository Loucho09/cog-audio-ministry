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
                    'title' => 'Front of House',
                    'tag' => 'FOH',
                    'description' => 'FOH mixes the sound the congregation hears, balancing vocals and instruments so every word and song comes through clearly.',
                    'icon' => '<path stroke-linecap="round" d="M6 4v16M12 4v16M18 4v16M4 9h4M10 15h4M16 8h4" />',
                ],
                [
                    'title' => 'Monitors',
                    'tag' => 'On stage',
                    'description' => 'The monitor mix is what singers and musicians hear on stage, so they can hear themselves and each other while they serve.',
                    'icon' => '<rect x="5.5" y="3" width="13" height="18" rx="2" /><circle cx="12" cy="14" r="3.5" /><circle cx="12" cy="7.5" r="1.25" />',
                ],
                [
                    'title' => 'Livestream',
                    'tag' => 'Online',
                    'description' => 'The livestream team shares our services online, with clear sound and steady video for those who join from home.',
                    'icon' => '<rect x="3" y="6.5" width="13" height="11" rx="2" /><path stroke-linejoin="round" d="M16 10.5l5-3v9l-5-3" />',
                ],
                [
                    'title' => 'Stage Tech',
                    'tag' => 'Setup',
                    'description' => 'Stage tech sets up and cares for microphones, cables, and instruments so the stage is ready before each service begins.',
                    'icon' => '<rect x="9" y="3" width="6" height="11" rx="3" /><path stroke-linecap="round" d="M6 11a6 6 0 0012 0M12 17v4" />',
                ],
            ];
        @endphp

        <ul class="mx-auto mt-12 grid max-w-5xl gap-5 sm:mt-16 md:grid-cols-2 md:gap-6">
            @foreach ($roles as $index => $role)
                <li class="group relative isolate flex flex-col gap-5 overflow-hidden rounded-3xl border border-text/15 bg-surface p-6 shadow-sm transition duration-300 hover:border-primary/30 hover:shadow-lg sm:flex-row sm:gap-6 sm:p-8 dark:hover:shadow-[0_0_40px_-12px] dark:hover:shadow-primary/25">
                    {{-- Stage-light bar along the top edge; brightens on hover. --}}
                    <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary to-accent opacity-60 transition-opacity duration-300 group-hover:opacity-100"></span>

                    {{-- Soft watermark number, decorative only. --}}
                    <span aria-hidden="true" class="pointer-events-none absolute -z-10 right-5 top-3 select-none text-6xl font-extrabold leading-none tracking-tight text-text/[0.06] sm:right-7 sm:top-5">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>

                    {{-- Glowing icon tile: gradient from primary to accent at low strength, with a ring. --}}
                    <span aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-accent/15 text-primary-dark ring-1 ring-inset ring-primary/20 transition duration-300 motion-safe:group-hover:scale-105">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            {!! $role['icon'] !!}
                        </svg>
                    </span>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 pr-12">
                            <h3 class="text-lg font-bold text-text sm:text-xl">{{ $role['title'] }}</h3>
                            <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-primary-dark">{{ $role['tag'] }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-relaxed text-text/80 sm:text-[15px]">
                            {{ $role['description'] }}
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
