<x-app-layout :title="__('Dashboard')">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ __('Dashboard') }}</h1>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="space-y-6 sm:space-y-8">
        <section data-reveal aria-labelledby="welcome-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="welcome-heading" class="text-xl font-semibold text-text sm:text-2xl">
                {{ __($greeting) }}, {{ auth()->user()->name }}
            </h2>
            <p class="mt-2 text-sm text-text/70">{{ __('Thank you for serving with the COG Audio Ministry.') }}</p>
        </section>

        <div class="grid gap-6 sm:gap-8 lg:grid-cols-2">
            <section data-reveal aria-labelledby="branch-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                <h2 id="branch-heading" class="text-lg font-semibold text-text">{{ __('Your branch') }}</h2>

                @if ($branch)
                    @php($facebook = $branch->safeFacebookUrl())
                    <p class="mt-3 text-base font-medium text-text">{{ $branch->name }}</p>

                    @if ($branch->address || $branch->contact_number || $branch->email || $facebook)
                        <dl class="mt-4 space-y-3 text-sm">
                            @if ($branch->address)
                                <div>
                                    <dt class="font-medium text-text/70">{{ __('Address') }}</dt>
                                    <dd class="mt-0.5 text-text">{{ $branch->address }}</dd>
                                </div>
                            @endif
                            @if ($branch->contact_number)
                                <div>
                                    <dt class="font-medium text-text/70">{{ __('Contact') }}</dt>
                                    <dd class="mt-0.5 text-text">{{ $branch->contact_number }}</dd>
                                </div>
                            @endif
                            @if ($branch->email)
                                <div>
                                    <dt class="font-medium text-text/70">{{ __('Email') }}</dt>
                                    <dd class="mt-0.5 break-words text-text">{{ $branch->email }}</dd>
                                </div>
                            @endif
                            @if ($facebook)
                                <div>
                                    <dt class="font-medium text-text/70">{{ __('Facebook') }}</dt>
                                    <dd class="mt-0.5 break-words">
                                        <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="text-primary underline underline-offset-2 hover:no-underline">{{ __('Visit our Facebook page') }}</a>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-text/70">{{ __('Your branch has not added its details yet. Please check back soon.') }}</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-text/70">{{ __('You are not assigned to a branch yet. Please ask your branch head.') }}</p>
                @endif
            </section>

            <section data-reveal data-reveal-delay="80" aria-labelledby="services-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                <h2 id="services-heading" class="text-lg font-semibold text-text">{{ __('Upcoming services') }}</h2>

                @forelse ($serviceGroups as $group)
                    <div class="mt-4">
                        <h3 class="text-sm font-semibold text-text">
                            {{ $group['branch']->name }}
                            @if ($group['is_own'])
                                <span class="ms-1 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">{{ __('Your branch') }}</span>
                            @endif
                        </h3>
                        <ul class="mt-2 divide-y divide-text/10 text-sm">
                            @foreach ($group['occurrences'] as $item)
                                <li class="flex flex-col py-2 sm:flex-row sm:items-baseline sm:justify-between sm:gap-x-4">
                                    <span class="text-text">{{ $item['service']->name }}</span>
                                    <time datetime="{{ $item['starts_at']->toIso8601String() }}" class="text-text/70">
                                        {{ $item['starts_at']->format('D, M j') }} &middot; {{ $item['starts_at']->format('g:i A') }}
                                    </time>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-text/70">{{ __('No upcoming services are scheduled yet.') }}</p>
                @endforelse
            </section>
        </div>

        <section data-reveal aria-labelledby="links-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="links-heading" class="text-lg font-semibold text-text">{{ __('Quick links') }}</h2>
            <ul class="mt-4 flex flex-col gap-3 text-sm sm:flex-row sm:flex-wrap sm:gap-6">
                <li><a href="{{ route('profile.edit') }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Edit profile') }}</a></li>
                <li><a href="{{ route('password.change') }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Change password') }}</a></li>
                @if ($isAdmin)
                    <li><a href="{{ filament()->getPanel('admin')->getUrl() }}" class="font-medium text-primary underline underline-offset-2 hover:no-underline">{{ __('Admin panel') }}</a></li>
                @endif
            </ul>
        </section>

        <div class="grid gap-6 sm:grid-cols-2 sm:gap-8">
            <section data-reveal aria-labelledby="duties-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                <h2 id="duties-heading" class="text-lg font-semibold text-text">{{ __('My upcoming duties') }}</h2>

                @if (session('error'))
                    <p role="alert" class="mt-3 rounded-xl border border-accent-text/40 bg-accent/10 px-3 py-2 text-sm text-accent-text">{{ session('error') }}</p>
                @endif

                @if ($duties->isEmpty())
                    <p class="mt-3 text-sm text-text/70">{{ __('No duties yet. Your branch leaders will assign you.') }}</p>
                @else
                    <ul class="mt-4 divide-y divide-text/10">
                        @foreach ($duties as $duty)
                            <li class="py-4 first:pt-0 last:pb-0">
                                <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
                                    <p class="min-w-0 break-words font-medium text-text">{{ $duty->event->name }}</p>
                                    <span class="shrink-0 rounded-full bg-text/10 px-2 py-0.5 text-xs font-medium text-text">{{ __($duty->status->label()) }}</span>
                                </div>
                                <p class="mt-1 text-sm text-text/70">
                                    <time datetime="{{ $duty->event->starts_at->toIso8601String() }}">{{ $duty->event->starts_at->timezone(config('app.timezone'))->format('D, M j · g:i A') }}</time>
                                </p>
                                <p class="mt-0.5 text-sm text-text/70">{{ $duty->event->branch->name }} &middot; {{ $duty->dutyRole->name }}</p>

                                @if ($duty->status === \App\Enums\DutyStatus::Assigned && ! $duty->event->hasStarted())
                                    <div class="mt-3 flex flex-wrap gap-3">
                                        <form method="POST" action="{{ route('duties.accept', $duty) }}" data-submit-guard>
                                            @csrf
                                            <x-primary-button aria-label="{{ __('Accept :event duty', ['event' => $duty->event->name]) }}">{{ __('Accept') }}</x-primary-button>
                                        </form>
                                        <form method="POST" action="{{ route('duties.decline', $duty) }}" data-submit-guard onsubmit="return confirm(@js(__("Decline this duty? You won't be able to take it back.")))">
                                            @csrf
                                            <x-secondary-button type="submit" aria-label="{{ __('Decline :event duty', ['event' => $duty->event->name]) }}">{{ __('Decline') }}</x-secondary-button>
                                        </form>
                                    </div>
                                @endif

                                @if ($duty->status === \App\Enums\DutyStatus::Accepted && ! $duty->event->hasStarted())
                                    <form method="POST" action="{{ route('swaps.store', $duty) }}" data-submit-guard class="mt-3" onsubmit="return confirm(@js(__('Offer this duty to another member? Your admin will confirm the swap.')))">
                                        @csrf
                                        <x-secondary-button type="submit" aria-label="{{ __('Offer swap for :event', ['event' => $duty->event->name]) }}">{{ __('Offer swap') }}</x-secondary-button>
                                    </form>
                                @endif

                                @if ($duty->status === \App\Enums\DutyStatus::SwapRequested && ($swap = $duty->swaps->sortByDesc('id')->first()))
                                    <p class="mt-2 text-sm text-text/70">
                                        @if ($swap->claimedBy)
                                            {{ __('Claimed by :name, waiting for admin.', ['name' => $swap->claimedBy->name]) }}
                                        @else
                                            {{ __('Waiting for someone to take this duty.') }}
                                        @endif
                                    </p>
                                    @if ((int) $swap->offered_by === (int) auth()->id())
                                        <form method="POST" action="{{ route('swaps.cancel', $swap) }}" data-submit-guard class="mt-3" onsubmit="return confirm(@js(__('Cancel this swap and keep the duty?')))">
                                            @csrf
                                            <x-secondary-button type="submit" aria-label="{{ __('Cancel swap for :event', ['event' => $duty->event->name]) }}">{{ __('Cancel swap') }}</x-secondary-button>
                                        </form>
                                    @endif
                                @endif

                                @if ($duty->status === \App\Enums\DutyStatus::SwapRequested && $duty->swaps->isEmpty())
                                    <p class="mt-2 text-sm text-text/70">{{ __("Swap expired. You're still on this duty.") }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section data-reveal data-reveal-delay="80" aria-labelledby="announcements-heading" class="rounded-2xl border border-dashed border-text/30 bg-background p-6 sm:p-8">
                <h2 id="announcements-heading" class="text-lg font-semibold text-text">{{ __('Announcements') }}</h2>
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-accent-text">{{ __('Coming soon') }}</p>
                <p class="mt-3 text-sm text-text/70">{{ __('News from your branch will appear here.') }}</p>
            </section>
        </div>

        <section data-reveal aria-labelledby="swaps-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="swaps-heading" class="text-lg font-semibold text-text">{{ __('Open swaps in your branch') }}</h2>
            <p class="mt-1 text-sm text-text/70">{{ __('A teammate cannot serve and is looking for someone to take their duty.') }}</p>

            @if ($claimedSwaps->isNotEmpty())
                <ul class="mt-4 space-y-2">
                    @foreach ($claimedSwaps as $claimedSwap)
                        <li class="rounded-xl border border-primary/30 bg-primary/5 px-3 py-2 text-sm text-text">
                            {{ __('You offered to take :role at :event, :when. Waiting for your admin.', [
                                'role' => $claimedSwap->assignment->dutyRole->name,
                                'event' => $claimedSwap->assignment->event->name,
                                'when' => $claimedSwap->assignment->event->starts_at->timezone(config('app.timezone'))->format('D, M j · g:i A'),
                            ]) }}
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($openSwaps->isEmpty())
                <p class="mt-3 text-sm text-text/70">{{ __('No open swaps right now.') }}</p>
            @else
                <ul class="mt-4 divide-y divide-text/10">
                    @foreach ($openSwaps as $openSwap)
                        @php($swapEvent = $openSwap->assignment->event)
                        <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 py-4 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="break-words font-medium text-text">
                                    {{ $swapEvent->name }}
                                    <span class="ms-2 rounded-full bg-text/10 px-2 py-0.5 text-xs font-medium text-text">{{ __($swapEvent->block()->label()) }}</span>
                                </p>
                                <p class="mt-1 text-sm text-text/70">
                                    <time datetime="{{ $swapEvent->starts_at->toIso8601String() }}">{{ $swapEvent->starts_at->timezone(config('app.timezone'))->format('D, M j · g:i A') }}</time>
                                </p>
                                <p class="mt-0.5 break-words text-sm text-text/70">{{ $openSwap->assignment->dutyRole->name }} &middot; {{ __('offered by :name', ['name' => $openSwap->offeredBy->name]) }}</p>
                            </div>
                            <form method="POST" action="{{ route('swaps.claim', $openSwap) }}" data-submit-guard>
                                @csrf
                                <x-primary-button aria-label="{{ __('Claim swap for :event', ['event' => $swapEvent->name]) }}">{{ __('Claim') }}</x-primary-button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @can('create', \App\Models\MemberUnavailability::class)
            <section data-reveal aria-labelledby="availability-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                <h2 id="availability-heading" class="text-lg font-semibold text-text">{{ __('My availability') }}</h2>
                <p class="mt-1 text-sm text-text/70">
                    {{ __("Let your admin know when you can't serve. AM = services before :time, PM = :time onward.", ['time' => \Illuminate\Support\Carbon::createFromFormat('H:i', config('ministry.duty_pm_starts_at'))->format('g:i A')]) }}
                </p>

                @if (session('availability_error'))
                    <p role="alert" class="mt-3 rounded-xl border border-accent-text/40 bg-accent/10 px-3 py-2 text-sm text-accent-text">{{ session('availability_error') }}</p>
                @endif
                @if ($errors->any())
                    <ul role="alert" class="mt-3 list-inside list-disc rounded-xl border border-accent-text/40 bg-accent/10 px-3 py-2 text-sm text-accent-text">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                @endif

                <form method="POST" action="{{ route('availability.store') }}" data-submit-guard class="mt-4 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <div>
                        <label for="availability-date" class="block text-sm font-medium text-text">{{ __('Date') }}</label>
                        <input id="availability-date" name="date" type="date" required min="{{ now()->format('Y-m-d') }}" value="{{ old('date') }}" class="mt-1 block w-full rounded-xl border border-text/20 bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="availability-part" class="block text-sm font-medium text-text">{{ __('Part of the day') }}</label>
                        <select id="availability-part" name="part" required class="mt-1 block w-full rounded-xl border border-text/20 bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                            @foreach (\App\Enums\AvailabilityPart::cases() as $part)
                                <option value="{{ $part->value }}" @selected(old('part', 'whole_day') === $part->value)>{{ __($part->label()) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="availability-note" class="block text-sm font-medium text-text">{{ __('Note (optional)') }}</label>
                        <input id="availability-note" name="note" type="text" maxlength="100" value="{{ old('note') }}" class="mt-1 block w-full rounded-xl border border-text/20 bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                    </div>
                    <div class="sm:col-span-2">
                        <x-primary-button>{{ __('Mark unavailable') }}</x-primary-button>
                    </div>
                </form>

                @if ($availability->isEmpty())
                    <p class="mt-6 text-sm text-text/70">{{ __('You have not marked any dates. You are open to be assigned.') }}</p>
                @else
                    <ul class="mt-6 divide-y divide-text/10">
                        @foreach ($availability as $entry)
                            <li class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3 first:pt-0 last:pb-0">
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-text">
                                        <time datetime="{{ $entry->date->toDateString() }}">{{ $entry->date->format('D, M j') }}</time>
                                        <span class="ms-2 rounded-full bg-text/10 px-2 py-0.5 text-xs font-medium text-text">{{ __($entry->part->label()) }}</span>
                                    </p>
                                    @if ($entry->note)
                                        <p class="mt-0.5 break-words text-sm text-text/70">{{ $entry->note }}</p>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('availability.destroy', $entry) }}" data-submit-guard onsubmit="return confirm(@js(__('Remove this date?')))">
                                    @csrf
                                    @method('DELETE')
                                    <x-secondary-button type="submit" aria-label="{{ __('Remove :date', ['date' => $entry->date->format('D, M j')]) }}">{{ __('Remove') }}</x-secondary-button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endcan
    </div>
</x-app-layout>
