<section id="what-we-do" aria-labelledby="what-we-do-title" class="relative isolate overflow-hidden bg-background py-20 sm:py-24 lg:py-32">
    <x-site.section-backdrop variant="rings" fade-from="surface" />
    <div class="mx-auto max-w-7xl px-5 sm:px-6 lg:px-8">
        <x-site.section-header
            id="what-we-do-title"
            eyebrow="How we serve"
            title="What We Do"
            lead="Every service needs a team. Pick a role to see what it does."
        />

        <div class="mx-auto mt-6 max-w-xs" aria-hidden="true">
            <x-site.wave-divider gradient-id="what-we-do-wave" />
        </div>

        @php
            // Icon markup is static SVG paths defined here, never user input.
            $roles = [
                [
                    'title' => 'Front of House',
                    'short' => 'FOH',
                    'tag' => 'FOH',
                    'summary' => 'Mixes the sound the congregation hears.',
                    'description' => 'FOH balances vocals and instruments so every word and song comes through clearly.',
                    'icon' => '<path stroke-linecap="round" d="M6 4v16M12 4v16M18 4v16M4 9h4M10 15h4M16 8h4" />',
                ],
                [
                    'title' => 'Monitors',
                    'short' => 'Monitors',
                    'tag' => 'On stage',
                    'summary' => 'Mixes what singers and musicians hear on stage.',
                    'description' => 'So they can hear themselves and each other while they serve.',
                    'icon' => '<rect x="5.5" y="3" width="13" height="18" rx="2" /><circle cx="12" cy="14" r="3.5" /><circle cx="12" cy="7.5" r="1.25" />',
                ],
                [
                    'title' => 'Livestream',
                    'short' => 'Livestream',
                    'tag' => 'Online',
                    'summary' => 'Shares our services with those at home.',
                    'description' => 'Clear sound and steady video for everyone who joins online.',
                    'icon' => '<rect x="3" y="6.5" width="13" height="11" rx="2" /><path stroke-linejoin="round" d="M16 10.5l5-3v9l-5-3" />',
                ],
                [
                    'title' => 'Stage Tech',
                    'short' => 'Stage Tech',
                    'tag' => 'Setup',
                    'summary' => 'Gets the stage ready before each service.',
                    'description' => 'Sets up and cares for microphones, cables, and instruments.',
                    'icon' => '<rect x="9" y="3" width="6" height="11" rx="3" /><path stroke-linecap="round" d="M6 11a6 6 0 0012 0M12 17v4" />',
                ],
            ];
            $count = count($roles);
        @endphp

        <div
            class="mx-auto mt-12 sm:mt-16 lg:grid lg:grid-cols-12 lg:items-start lg:gap-8"
            x-data="{
                active: 0,
                count: {{ $count }},
                // Desktop (lg+) tabs are a vertical list, so Up/Down move between them instead of Left/Right.
                vertical: window.matchMedia('(min-width: 1024px)').matches,
                init() {
                    this._mq = window.matchMedia('(min-width: 1024px)');
                    this._onMq = (e) => { this.vertical = e.matches; };
                    this._mq.addEventListener('change', this._onMq);
                },
                destroy() {
                    this._mq?.removeEventListener('change', this._onMq);
                },
                select(i, focus = false) {
                    this.active = (i + this.count) % this.count;
                    if (focus) this.$nextTick(() => this.$refs['roleTab' + this.active].focus());
                },
                onKey(e) {
                    {{-- Leave Alt/Ctrl/Meta combos (e.g. Alt+Left = browser Back) alone. --}}
                    if (e.altKey || e.ctrlKey || e.metaKey) return;
                    const next = this.vertical ? 'ArrowDown' : 'ArrowRight';
                    const prev = this.vertical ? 'ArrowUp' : 'ArrowLeft';
                    const target = { [next]: this.active + 1, [prev]: this.active - 1, Home: 0, End: this.count - 1 }[e.key];
                    if (target === undefined) return;
                    e.preventDefault();
                    this.select(target, true);
                },
            }"
        >
            {{-- Role picker: WAI-ARIA tabs pattern (arrow keys, Home/End). Horizontal on mobile/tablet, vertical on lg+. --}}
            <div
                role="tablist"
                aria-label="Audio ministry roles"
                :aria-orientation="vertical ? 'vertical' : 'horizontal'"
                class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:col-span-4 lg:grid-cols-1 lg:gap-3"
                @keydown="onKey($event)"
            >
                @foreach ($roles as $index => $role)
                    <button
                        type="button"
                        role="tab"
                        id="role-tab-{{ $index }}"
                        aria-controls="role-panel-{{ $index }}"
                        aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                        tabindex="{{ $index === 0 ? '0' : '-1' }}"
                        x-ref="roleTab{{ $index }}"
                        :aria-selected="(active === {{ $index }}).toString()"
                        :tabindex="active === {{ $index }} ? 0 : -1"
                        @click="select({{ $index }})"
                        @class([
                            'group relative flex flex-col items-center gap-2 overflow-hidden rounded-2xl border px-2 py-4 text-center text-sm font-semibold transition duration-300 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background motion-reduce:transition-none sm:px-4 sm:py-5 lg:flex-row lg:justify-start lg:gap-4 lg:px-5 lg:py-4 lg:text-left',
                            'border-primary/40 bg-surface text-text shadow-md dark:shadow-[0_0_30px_-10px] dark:shadow-primary/40' => $index === 0,
                            'border-text/15 bg-surface/60 text-text/80 hover:border-primary/30 hover:bg-surface hover:text-text' => $index !== 0,
                        ])
                        :class="{
                            'border-primary/40 bg-surface text-text shadow-md dark:shadow-[0_0_30px_-10px] dark:shadow-primary/40': active === {{ $index }},
                            'border-text/15 bg-surface/60 text-text/80 hover:border-primary/30 hover:bg-surface hover:text-text': active !== {{ $index }},
                        }"
                    >
                        <span aria-hidden="true"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary/15 to-accent/15 text-primary-dark ring-1 ring-inset ring-primary/20 transition duration-300 motion-safe:group-hover:scale-105 sm:h-12 sm:w-12">
                            <svg class="h-5 w-5 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                {!! $role['icon'] !!}
                            </svg>
                        </span>
                        <span>{{ $role['short'] }}</span>

                        {{-- Active indicator: stage-light bar along the bottom edge. --}}
                        <span aria-hidden="true"
                            @class([
                                'absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-primary to-accent transition-opacity duration-300 motion-reduce:transition-none lg:inset-x-auto lg:inset-y-0 lg:left-0 lg:h-auto lg:w-1 lg:bg-gradient-to-b',
                                'opacity-0' => $index !== 0,
                            ])
                            :class="{ 'opacity-100': active === {{ $index }}, 'opacity-0': active !== {{ $index }} }"></span>
                    </button>
                @endforeach
            </div>

            {{-- Panels share one grid cell so the height never jumps between roles. --}}
            <div class="mt-5 grid sm:mt-6 lg:col-span-8 lg:mt-0">
                @foreach ($roles as $index => $role)
                    <div
                        role="tabpanel"
                        id="role-panel-{{ $index }}"
                        aria-labelledby="role-tab-{{ $index }}"
                        tabindex="0"
                        @class([
                            'relative isolate overflow-hidden rounded-3xl border border-text/15 bg-surface p-6 shadow-sm transition-opacity duration-300 [grid-area:1/1] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:p-10 motion-reduce:transition-none',
                            'invisible opacity-0' => $index !== 0,
                        ])
                        :class="{ 'invisible opacity-0': active !== {{ $index }} }"
                    >
                        <span aria-hidden="true" class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary to-accent"></span>
                        <span aria-hidden="true" class="pointer-events-none absolute -z-10 right-5 top-3 select-none text-7xl font-extrabold leading-none tracking-tight text-text/[0.06] sm:right-8 sm:top-6 sm:text-8xl">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>

                        <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:gap-8">
                            <span aria-hidden="true" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/15 to-accent/15 text-primary-dark ring-1 ring-inset ring-primary/20 sm:h-20 sm:w-20">
                                <svg class="h-8 w-8 sm:h-10 sm:w-10" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                    {!! $role['icon'] !!}
                                </svg>
                            </span>

                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2 pr-14">
                                    <h3 class="text-xl font-bold text-text sm:text-2xl">{{ $role['title'] }}</h3>
                                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-primary-dark">{{ $role['tag'] }}</span>
                                </div>
                                <p class="mt-3 text-base font-semibold text-text sm:text-lg">{{ $role['summary'] }}</p>
                                <p class="mt-1.5 text-sm leading-relaxed text-text/80 sm:text-[15px]">{{ $role['description'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
