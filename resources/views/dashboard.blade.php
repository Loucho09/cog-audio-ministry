<x-app-layout :title="__('Dashboard')">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ __('Dashboard') }}</h1>
    </x-slot>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <div class="space-y-6 sm:space-y-8">
        <section aria-labelledby="welcome-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="welcome-heading" class="text-xl font-semibold text-text sm:text-2xl">
                {{ __($greeting) }}, {{ auth()->user()->name }}
            </h2>
            <p class="mt-2 text-sm text-text/70">{{ __('Thank you for serving with the COG Audio Ministry.') }}</p>
        </section>

        <div class="grid gap-6 sm:gap-8 lg:grid-cols-2">
            <section aria-labelledby="branch-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
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

            <section aria-labelledby="services-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
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
                                <li class="flex flex-wrap items-baseline justify-between gap-x-4 py-2">
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

        <section aria-labelledby="links-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
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
            <section aria-labelledby="duties-heading" class="rounded-2xl border border-dashed border-text/30 bg-background p-6 sm:p-8">
                <h2 id="duties-heading" class="text-lg font-semibold text-text">{{ __('Duties') }}</h2>
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-accent-text">{{ __('Coming soon') }}</p>
                <p class="mt-3 text-sm text-text/70">{{ __('Your duties and schedule will appear here.') }}</p>
            </section>

            <section aria-labelledby="announcements-heading" class="rounded-2xl border border-dashed border-text/30 bg-background p-6 sm:p-8">
                <h2 id="announcements-heading" class="text-lg font-semibold text-text">{{ __('Announcements') }}</h2>
                <p class="mt-1 text-xs font-medium uppercase tracking-wide text-accent-text">{{ __('Coming soon') }}</p>
                <p class="mt-3 text-sm text-text/70">{{ __('News from your branch will appear here.') }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
