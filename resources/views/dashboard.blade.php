<x-app-layout :title="__('Dashboard')">
    <x-slot name="header">
        <h1 class="text-2xl font-bold tracking-tight text-text sm:text-3xl">{{ __('Dashboard') }}</h1>
    </x-slot>

    <section aria-labelledby="welcome-heading" class="rounded-2xl border border-text/15 bg-surface p-6 shadow-sm sm:p-8">
        <h2 id="welcome-heading" class="text-xl font-semibold text-text sm:text-2xl">
            {{ __('Welcome, :name', ['name' => auth()->user()->name]) }}
        </h2>

        <p class="mt-2 text-sm text-text/70">
            {{ __('Branch') }}: <span class="font-medium text-text">{{ auth()->user()->branch?->name ?? '—' }}</span>
        </p>

        {{-- TODO Phase 4: duties, schedule and swaps for this member go here. --}}
        <div class="mt-6 rounded-xl border border-dashed border-text/30 bg-background p-4">
            <p class="text-sm text-text/70">{{ __('Your duties and schedule will appear here soon.') }}</p>
        </div>
    </section>
</x-app-layout>
