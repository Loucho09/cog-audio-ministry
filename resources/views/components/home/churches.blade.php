{{-- CH 04. One channel strip per church, read from the branches table: the main church is the wide master strip.
     Addresses that are not in the database yet show a clear "to be added" note, never a guess. --}}
@props(['churches' => collect()])

@php
    $count = $churches->count();
    $words = [1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten'];
    $countWord = $words[$count] ?? (string) $count;
    $title = 'One sound, '.$countWord.' '.\Illuminate\Support\Str::plural('church', $count);
    $master = $churches->first(fn ($church) => $church->is_main);
    $others = $churches->reject(fn ($church) => $master && $church->is($master))->values();
    // Static fader positions (percent from the bottom), decoration only.
    $levels = [72, 58, 66, 48, 62, 54];
@endphp

<x-section id="churches">
    <div data-fx="fader-heading">
        <x-section-heading
            id="churches"
            channel="04"
            eyebrow="Our churches"
            :title="$title"
            description="The same heart for worship, shared across every church we serve."
        />
    </div>

    @if ($count > 0)
        <div class="mt-12 flex flex-col gap-6 lg:mt-16 lg:gap-8" data-fx="strips">
            @if ($master)
                <article data-strip class="grid gap-6 rounded-console border border-text/10 bg-surface p-6 lg:grid-cols-12 lg:gap-8 lg:p-8">
                    <div class="flex items-start gap-5 lg:col-span-5">
                        <div role="img" aria-label="Church of God logo: We Exalt You Jesus" class="h-16 w-16 shrink-0 overflow-hidden rounded-console">
                            <x-skeleton-image
                                src="{{ asset('images/cog-church-logo.png') }}"
                                alt=""
                                aria-hidden="true"
                                width="960"
                                height="960"
                                class="h-full w-full"
                                img-class="object-contain"
                            />
                        </div>
                        <div class="min-w-0">
                            <p class="t-label text-text/60">Strip 01 · Master</p>
                            <h3 class="mt-2 break-words text-2xl font-semibold tracking-tight lg:text-3xl">{{ $master->name }}</h3>
                            <p class="mt-2 inline-block rounded-console border border-primary/40 px-2 py-0.5 font-mono text-[11px] font-medium uppercase tracking-[0.12em] text-primary-dark dark:text-primary">Main church</p>
                        </div>
                    </div>

                    <div class="lg:col-span-5 lg:col-start-7">
                        <p class="t-label text-text/60">Address</p>
                        @if (filled($master->address))
                            <p class="mt-2 leading-relaxed">{{ $master->address }}</p>
                        @else
                            {{-- TODO: add the address in Filament (Branches). --}}
                            <p class="mt-2 font-mono text-sm text-text/50">Address to be added.</p>
                        @endif

                        @if ($master->safeFacebookUrl())
                            <a
                                href="{{ $master->safeFacebookUrl() }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="{{ $master->name }} on Facebook (opens in a new tab)"
                                class="link-fader mt-4 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal dark:text-primary"
                            >
                                <x-tabler-icon name="brand-facebook" class="h-4 w-4" />
                                Facebook
                            </a>
                        @endif
                    </div>

                    <div aria-hidden="true" class="hidden justify-self-end lg:col-span-1 lg:flex">
                        <div class="relative h-24 w-px bg-text/20">
                            <span class="absolute -left-2.5 h-3 w-5 rounded-sm bg-text" style="bottom: {{ $levels[0] }}%"></span>
                        </div>
                    </div>
                </article>
            @endif

            @if ($others->isNotEmpty())
                <ul class="flex flex-wrap gap-6 lg:gap-8">
                    @foreach ($others as $church)
                        <li data-strip class="flex min-w-[15rem] flex-1 basis-60 justify-between gap-4 rounded-console border border-text/10 bg-surface p-6">
                            <div class="flex min-w-0 flex-col">
                                <p class="t-label text-text/60">Strip {{ str_pad((string) ($loop->index + 2), 2, '0', STR_PAD_LEFT) }}</p>
                                <h3 class="mt-2 break-words text-xl font-semibold tracking-tight">{{ $church->name }}</h3>

                                @if (filled($church->address))
                                    <p class="mt-3 text-sm leading-relaxed text-text/70">{{ $church->address }}</p>
                                @else
                                    {{-- TODO: add the address in Filament (Branches). --}}
                                    <p class="mt-3 font-mono text-xs text-text/50">Address to be added.</p>
                                @endif

                                @if ($church->safeFacebookUrl())
                                    <a
                                        href="{{ $church->safeFacebookUrl() }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="{{ $church->name }} on Facebook (opens in a new tab)"
                                        class="link-fader mt-auto inline-flex min-h-11 w-fit items-center gap-2 pt-4 text-sm font-medium text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-signal dark:text-primary"
                                    >
                                        <x-tabler-icon name="brand-facebook" class="h-4 w-4" />
                                        Facebook
                                    </a>
                                @endif
                            </div>

                            <div aria-hidden="true" class="flex shrink-0 items-stretch py-1">
                                <div class="relative w-px bg-text/20">
                                    <span class="absolute -left-2 h-2.5 w-4 rounded-sm bg-text" style="bottom: {{ $levels[$loop->index + 1] ?? 55 }}%"></span>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</x-section>
