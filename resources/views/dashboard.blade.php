<x-app-layout :title="__('Dashboard')">
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $card = 'rounded-2xl border border-text/15 bg-surface p-5 shadow-sm sm:p-8';
    @endphp

    <div class="space-y-6 sm:space-y-8">
        <section data-reveal aria-labelledby="welcome-heading" class="{{ $card }}">
            <h1 id="welcome-heading" class="text-2xl font-bold tracking-tight text-text sm:text-3xl">
                {{ __($greeting) }}, {{ auth()->user()->name }}
            </h1>
            <p class="mt-2 text-sm text-text/70">{{ __('Thank you for serving with the COG Audio Ministry.') }}</p>

            <nav aria-label="{{ __('Quick links') }}" class="mt-4 flex flex-wrap gap-3">
                <x-site.button variant="secondary" :href="route('profile.edit')">{{ __('Edit profile') }}</x-site.button>
                <x-site.button variant="secondary" :href="route('password.change')">{{ __('Change password') }}</x-site.button>
                @if ($isAdmin)
                    <x-site.button variant="secondary" :href="filament()->getPanel('admin')->getUrl()">{{ __('Admin panel') }}</x-site.button>
                @endif
            </nav>
        </section>

        @if (session('error'))
            <p role="alert" class="rounded-xl border border-accent-text/40 bg-accent/10 px-3 py-2 text-sm text-accent-text">{{ session('error') }}</p>
        @endif

        @if ($needsReply->isNotEmpty())
            <section data-reveal aria-labelledby="reply-heading" class="rounded-2xl border border-primary/40 bg-primary/5 p-5 shadow-sm sm:p-8">
                <h2 id="reply-heading" class="text-lg font-semibold text-text">{{ __('Needs your reply') }}</h2>
                <p class="mt-1 text-sm text-text/70">{{ __('Please let your admin know if you can serve.') }}</p>
                <ul class="mt-4 divide-y divide-text/10">
                    @foreach ($needsReply as $duty)
                        <li class="py-4 first:pt-0 last:pb-0"><x-duty-row :duty="$duty" /></li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section data-reveal aria-labelledby="duties-heading" class="{{ $card }}">
            <h2 id="duties-heading" class="text-lg font-semibold text-text">{{ __('My upcoming duties') }}</h2>

            @if ($duties->isEmpty())
                <p class="mt-3 text-sm text-text/70">{{ __('No duties yet. Your branch admin will assign you.') }}</p>
            @else
                @if ($upcomingDuties->isEmpty())
                    <p class="mt-3 text-sm text-text/70">{{ $needsReply->isNotEmpty() ? __('Once you accept a duty above, it will show here.') : __('Duties you accept will show up here.') }}</p>
                @else
                    <ul class="mt-4 divide-y divide-text/10">
                        @foreach ($upcomingDuties->take(5) as $duty)
                            <li class="py-4 first:pt-0 last:pb-0"><x-duty-row :duty="$duty" /></li>
                        @endforeach
                    </ul>
                    @if ($upcomingDuties->count() > 5)
                        <details class="group mt-4 border-t border-text/10">
                            <summary class="flex min-h-11 cursor-pointer items-center text-sm font-medium text-primary-dark underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">
                                <span class="group-open:hidden">{{ __('Show all (:count)', ['count' => $upcomingDuties->count()]) }}</span>
                                <span class="hidden group-open:inline">{{ __('Show fewer') }}</span>
                            </summary>
                            <ul class="divide-y divide-text/10">
                                @foreach ($upcomingDuties->slice(5) as $duty)
                                    <li class="py-4 last:pb-0"><x-duty-row :duty="$duty" /></li>
                                @endforeach
                            </ul>
                        </details>
                    @endif
                @endif

                @if ($declinedDuties->isNotEmpty())
                    <details class="mt-4 border-t border-text/10">
                        <summary class="flex min-h-11 cursor-pointer items-center text-sm font-medium text-text/70 hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">
                            {{ __('Declined (:count)', ['count' => $declinedDuties->count()]) }}
                        </summary>
                        <ul class="divide-y divide-text/10">
                            @foreach ($declinedDuties as $duty)
                                <li class="py-4 last:pb-0"><x-duty-row :duty="$duty" /></li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @endif
        </section>

        <div class="grid items-start gap-6 sm:gap-8 lg:grid-cols-2">
            <section data-reveal aria-labelledby="swaps-heading" class="{{ $card }}">
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
                            @php $swapEvent = $openSwap->assignment->event; @endphp
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
                <section data-reveal data-reveal-delay="80" aria-labelledby="availability-heading" class="{{ $card }}">
                    <h2 id="availability-heading" class="text-lg font-semibold text-text">{{ __('My availability') }}</h2>
                    <p class="mt-1 text-sm text-text/70">
                        {{ __("Let your admin know when you can't serve. AM = before :time, PM = :time and later.", ['time' => \Illuminate\Support\Carbon::createFromFormat('H:i', config('ministry.duty_pm_starts_at'))->format('g:i A')]) }}
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
                            <input id="availability-date" name="date" type="date" required min="{{ now()->format('Y-m-d') }}" value="{{ old('date') }}" class="mt-1 block min-h-11 w-full appearance-none rounded-xl border border-text/20 bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label for="availability-part" class="block text-sm font-medium text-text">{{ __('Part of the day') }}</label>
                            <select id="availability-part" name="part" required class="mt-1 block min-h-11 w-full rounded-xl border border-text/20 bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
                                @foreach (\App\Enums\AvailabilityPart::cases() as $part)
                                    <option value="{{ $part->value }}" @selected(old('part', 'whole_day') === $part->value)>{{ __($part->label()) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="availability-note" class="block text-sm font-medium text-text">{{ __('Note (optional)') }}</label>
                            <input id="availability-note" name="note" type="text" maxlength="100" value="{{ old('note') }}" class="mt-1 block min-h-11 w-full rounded-xl border border-text/20 bg-background px-3 py-2 text-sm text-text focus:border-primary focus:ring-primary">
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

        @php
            $serviceRows = collect($serviceGroups)->flatMap(fn ($group) => collect($group['occurrences'])->map(fn ($item) => $item + ['branch' => $group['branch'], 'is_own' => $group['is_own']]));
        @endphp

        <div class="grid items-start gap-6 sm:gap-8 lg:grid-cols-2">
            <section data-reveal aria-labelledby="services-heading" class="{{ $card }}">
                <h2 id="services-heading" class="text-lg font-semibold text-text">{{ __('Upcoming services') }}</h2>

                @if ($serviceRows->isEmpty())
                    <p class="mt-3 text-sm text-text/70">{{ __('No upcoming services are scheduled yet.') }}</p>
                @else
                    <ul class="mt-3 divide-y divide-text/10 text-sm">
                        @foreach ($serviceRows->take(3) as $item)
                            @include('partials.dashboard-service-row', ['item' => $item])
                        @endforeach
                    </ul>
                    @if ($serviceRows->count() > 3)
                        <details class="group border-t border-text/10">
                            <summary class="flex min-h-11 cursor-pointer items-center text-sm font-medium text-primary-dark underline-offset-2 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">
                                <span class="group-open:hidden">{{ __('Show all (:count)', ['count' => $serviceRows->count()]) }}</span>
                                <span class="hidden group-open:inline">{{ __('Show fewer') }}</span>
                            </summary>
                            <ul class="divide-y divide-text/10 text-sm">
                                @foreach ($serviceRows->slice(3) as $item)
                                    @include('partials.dashboard-service-row', ['item' => $item])
                                @endforeach
                            </ul>
                        </details>
                    @endif
                @endif
            </section>

            <section data-reveal data-reveal-delay="80" aria-labelledby="branch-heading" class="{{ $card }}">
                <h2 id="branch-heading" class="text-lg font-semibold text-text">{{ __('Your branch') }}</h2>

                @if ($branch)
                    @php $facebook = $branch->safeFacebookUrl(); @endphp
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
                                        <a href="{{ $facebook }}" target="_blank" rel="noopener noreferrer" class="text-primary-dark underline underline-offset-2 hover:no-underline">{{ __('Visit our Facebook page') }}</a>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    @else
                        <p class="mt-3 text-sm text-text/70">{{ __('Your branch has not added its details yet. Please check back soon.') }}</p>
                    @endif
                @else
                    <p class="mt-3 text-sm text-text/70">{{ __('You are not assigned to a branch yet. Please ask your branch admin.') }}</p>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
