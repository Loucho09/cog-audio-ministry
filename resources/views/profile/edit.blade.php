<x-app-layout :title="__('Profile')">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ __('Profile') }}</h1>
    </x-slot>

    <div class="space-y-6">
        <section aria-labelledby="account-summary-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <h2 id="account-summary-heading" class="sr-only">{{ __('Account summary') }}</h2>
            <dl class="max-w-xl space-y-2 text-sm">
                <div class="flex flex-wrap gap-x-2">
                    <dt class="font-medium text-text">{{ __('Branch') }}:</dt>
                    <dd class="text-text/70">{{ $user->branch?->name ?? '—' }}</dd>
                </div>
                <div class="flex flex-wrap gap-x-2">
                    <dt class="font-medium text-text">{{ __('Role') }}:</dt>
                    <dd class="text-text/70">{{ $user->getRoleNames()->join(', ') ?: '—' }}</dd>
                </div>
            </dl>
        </section>

        <div class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-notifications-form')
            </div>
        </div>

        @if ($user->canSelfDelete())
            <div class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
