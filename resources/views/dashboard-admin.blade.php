<x-app-layout :title="__('Dashboard')">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ __('Dashboard') }}</h1>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php($tz = config('app.timezone'))
    @php($h = $isSuper ? 'h3' : 'h2')
    @php($h4 = $isSuper ? 'h4' : 'h3')

    <div class="space-y-6 sm:space-y-8">
        <section aria-labelledby="welcome-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="welcome-heading" class="text-xl font-semibold text-text sm:text-2xl">
                {{ __($greeting) }}, {{ auth()->user()->name }}
            </h2>
            <p class="mt-2 text-sm text-text/70">
                {{ $isSuper ? __('Here is how every branch is doing.') : __('Here is what needs your care today.') }}
            </p>
        </section>

        @if ($groups->isNotEmpty())
        <section aria-labelledby="actions-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="actions-heading" class="text-lg font-semibold text-text">{{ __('Quick actions') }}</h2>
            <ul class="mt-4 flex flex-col gap-3 text-sm sm:flex-row sm:flex-wrap sm:gap-6">
                <li><a href="{{ $links['event'] }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Create an event') }}</a></li>
                <li><a href="{{ $links['applications'] }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Join applications') }}</a></li>
                <li><a href="{{ $links['swaps'] }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Duty swaps') }}</a></li>
                <li><a href="{{ filament()->getPanel('admin')->getUrl() }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Admin panel') }}</a></li>
                <li><a href="{{ route('profile.edit') }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Edit profile') }}</a></li>
            </ul>
        </section>
        @endif

        @forelse ($groups as $group)
            @php($b = $group['branch'])
            @php($att = $group['attention'])

            <section @if ($isSuper) aria-labelledby="branch-{{ $b->id }}-heading" @endif class="space-y-6 sm:space-y-8">
                @if ($isSuper)
                    <h2 id="branch-{{ $b->id }}-heading" class="border-b border-text/15 pb-2 text-xl font-bold text-text">{{ $b->name }}</h2>
                @endif

                <section aria-labelledby="attention-{{ $b->id }}-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                    <{{ $h }} id="attention-{{ $b->id }}-heading" class="text-lg font-semibold text-text">{{ __('Needs attention') }}</{{ $h }}>

                    @if (array_sum($att) === 0)
                        <p class="mt-3 text-sm text-text/70">{{ __('All caught up. Nothing is waiting for you right now.') }}</p>
                    @else
                        <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-text/15 px-4 py-3">
                                <dt class="text-sm text-text/70">{{ __('Join applications to review') }}</dt>
                                <dd class="mt-1 text-2xl font-bold {{ $att['applications'] > 0 ? 'text-accent-text' : 'text-text' }}">
                                    @if ($att['applications'] > 0)
                                        <a href="{{ $links['applications'] }}" aria-label="{{ __('Join applications to review') }}: {{ $att['applications'] }}" class="underline underline-offset-2 hover:no-underline">{{ $att['applications'] }}</a>
                                    @else
                                        {{ $att['applications'] }}
                                    @endif
                                </dd>
                            </div>
                            <div class="rounded-xl border border-text/15 px-4 py-3">
                                <dt class="text-sm text-text/70">{{ __('Swaps to approve') }}</dt>
                                <dd class="mt-1 text-2xl font-bold {{ $att['swaps'] > 0 ? 'text-accent-text' : 'text-text' }}">
                                    @if ($att['swaps'] > 0)
                                        <a href="{{ $links['swaps'] }}" aria-label="{{ __('Swaps to approve') }}: {{ $att['swaps'] }}" class="underline underline-offset-2 hover:no-underline">{{ $att['swaps'] }}</a>
                                    @else
                                        {{ $att['swaps'] }}
                                    @endif
                                </dd>
                            </div>
                            <div class="rounded-xl border border-text/15 px-4 py-3">
                                <dt class="text-sm text-text/70">{{ __('Declined duties to refill') }}</dt>
                                <dd class="mt-1 text-2xl font-bold {{ $att['declined'] > 0 ? 'text-accent-text' : 'text-text' }}">{{ $att['declined'] }}</dd>
                            </div>
                        </dl>
                    @endif
                </section>

                <div class="grid gap-6 sm:gap-8 lg:grid-cols-2">
                    <section aria-labelledby="unfilled-{{ $b->id }}-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                        <{{ $h }} id="unfilled-{{ $b->id }}-heading" class="text-lg font-semibold text-text">{{ __('Open slots, next 14 days') }}</{{ $h }}>

                        @if ($group['unfilled']->isEmpty())
                            <p class="mt-3 text-sm text-text/70">{{ __('Every duty is filled for the next two weeks. Well done!') }}</p>
                        @else
                            <ul class="mt-4 divide-y divide-text/10">
                                @foreach ($group['unfilled']->take(6) as $row)
                                    <li class="py-3 first:pt-0 last:pb-0">
                                        <a href="{{ $row['url'] }}" class="break-words font-medium text-primary underline underline-offset-2 hover:no-underline">{{ $row['event']->name }}</a>
                                        <p class="mt-0.5 text-sm text-text/70">
                                            <time datetime="{{ $row['event']->starts_at->toIso8601String() }}">{{ $row['event']->starts_at->timezone($tz)->format('D, M j · g:i A') }}</time>
                                        </p>
                                        <p class="mt-0.5 text-sm text-text">{{ implode(', ', $row['gaps']) }}</p>
                                    </li>
                                @endforeach
                            </ul>
                            @if ($group['unfilled']->count() > 6)
                                <p class="mt-4 text-sm"><a href="{{ $links['events'] }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">+{{ $group['unfilled']->count() - 6 }} {{ __('more') }}</a></p>
                            @endif
                        @endif
                    </section>

                    <section aria-labelledby="waiting-{{ $b->id }}-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                        <{{ $h }} id="waiting-{{ $b->id }}-heading" class="text-lg font-semibold text-text">{{ __('Waiting for a reply') }}</{{ $h }}>

                        @if ($group['waiting']->isEmpty())
                            <p class="mt-3 text-sm text-text/70">{{ __('Everyone has replied. Nothing to chase.') }}</p>
                        @else
                            <ul class="mt-4 divide-y divide-text/10">
                                @foreach ($group['waiting'] as $duty)
                                    <li class="py-3 first:pt-0 last:pb-0">
                                        <p class="break-words font-medium text-text">{{ $duty->user->name }}</p>
                                        <p class="mt-0.5 text-sm text-text/70">{{ $duty->dutyRole->name }} &middot; {{ $duty->event->name }}</p>
                                        <p class="mt-0.5 text-sm text-text/70">
                                            <time datetime="{{ $duty->event->starts_at->toIso8601String() }}">{{ $duty->event->starts_at->timezone($tz)->format('D, M j · g:i A') }}</time>
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>

                <div class="grid gap-6 sm:gap-8 lg:grid-cols-2">
                    <section aria-labelledby="unavailable-{{ $b->id }}-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                        <{{ $h }} id="unavailable-{{ $b->id }}-heading" class="text-lg font-semibold text-text">{{ __('Who is unavailable, next 14 days') }}</{{ $h }}>

                        @if ($group['unavailable']->isEmpty())
                            <p class="mt-3 text-sm text-text/70">{{ __('No one has marked a day off yet.') }}</p>
                        @else
                            <ul class="mt-4 divide-y divide-text/10">
                                @foreach ($group['unavailable'] as $entry)
                                    <li class="py-3 first:pt-0 last:pb-0">
                                        <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                                            <p class="min-w-0 break-words font-medium text-text">{{ $entry->user->name }}</p>
                                            <span class="shrink-0 rounded-full bg-text/10 px-2 py-0.5 text-xs font-medium text-text">{{ __($entry->part->label()) }}</span>
                                        </div>
                                        <p class="mt-0.5 text-sm text-text/70">{{ $entry->date->format('D, M j') }}</p>
                                        @if ($entry->note)
                                            <p class="mt-0.5 break-words text-sm text-text/70">{{ $entry->note }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>

                    <section aria-labelledby="snapshot-{{ $b->id }}-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                        <{{ $h }} id="snapshot-{{ $b->id }}-heading" class="text-lg font-semibold text-text">{{ __('Branch snapshot') }}</{{ $h }}>
                        <dl class="mt-4 grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-xl border border-text/15 px-2 py-3">
                                <dt class="text-xs text-text/70">{{ __('Members') }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-text">{{ $group['snapshot']['members'] }}</dd>
                            </div>
                            <div class="rounded-xl border border-text/15 px-2 py-3">
                                <dt class="text-xs text-text/70">{{ __('Accepted, 30 days') }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-text">{{ $group['snapshot']['accepted'] }}</dd>
                            </div>
                            <div class="rounded-xl border border-text/15 px-2 py-3">
                                <dt class="text-xs text-text/70">{{ __('Declined, 30 days') }}</dt>
                                <dd class="mt-1 text-2xl font-bold text-text">{{ $group['snapshot']['declined'] }}</dd>
                            </div>
                        </dl>

                        <{{ $h4 }} class="mt-6 text-sm font-semibold text-text">{{ __('Upcoming services') }}</{{ $h4 }}>
                        @if ($group['snapshot']['services']->isEmpty())
                            <p class="mt-2 text-sm text-text/70">{{ __('No upcoming services are scheduled yet.') }}</p>
                        @else
                            <ul class="mt-2 divide-y divide-text/10 text-sm">
                                @foreach ($group['snapshot']['services'] as $item)
                                    <li class="flex flex-col py-2 sm:flex-row sm:items-baseline sm:justify-between sm:gap-x-4">
                                        <span class="text-text">{{ $item['service']->name }}</span>
                                        <time datetime="{{ $item['starts_at']->toIso8601String() }}" class="text-text/70">
                                            {{ $item['starts_at']->timezone($tz)->format('D, M j') }} &middot; {{ $item['starts_at']->timezone($tz)->format('g:i A') }}
                                        </time>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </section>
                </div>
            </section>
        @empty
            <p class="rounded-2xl border border-dashed border-text/30 bg-background p-6 text-sm text-text/70">{{ __('No branch is set up yet. Please ask the super admin to add you to a branch.') }}</p>
        @endforelse
    </div>
</x-app-layout>
