@php
    $user = auth()->user();
    // Panel access rules live in App\Models\User::canAccessPanel(); the link only appears when they allow it.
    try {
        $panel = \Filament\Facades\Filament::getPanel('admin');
        $canSeePanel = $user->canAccessPanel($panel);
    } catch (\Throwable) {
        $panel = null;
        $canSeePanel = false;
    }
@endphp

<header x-data="{ open: false }" @keydown.escape.window="if (open) { open = false; $refs.menuButton.focus() }" class="sticky top-0 z-50 w-full border-b border-text/15 bg-surface/90 backdrop-blur-md">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-5 sm:px-6 lg:px-8">

        {{-- Brand: links to the public homepage --}}
        <a
            href="{{ route('home') }}"
            class="flex min-h-11 min-w-0 items-center gap-3 rounded-md transition-opacity duration-200 hover:opacity-80 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark"
        >
            <x-site.logo />
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-[0.8125rem] font-bold tracking-tight text-text sm:text-base">{{ config('ministry.name') }}</span>
                <span class="block truncate text-xs text-text/70">{{ config('ministry.subheader') }}</span>
            </span>
        </a>

        <div class="flex shrink-0 items-center gap-1 sm:gap-2">

            {{-- Desktop links --}}
            <nav aria-label="Member" class="hidden sm:block">
                <ul class="flex items-center gap-1">
                    <li>
                        <x-nav-link :href="route('home')">Homepage</x-nav-link>
                    </li>
                    <li>
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                    </li>
                    <li>
                        <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">Profile</x-nav-link>
                    </li>
                    @if ($canSeePanel)
                        <li>
                            <x-nav-link :href="$panel->getUrl()">Admin panel</x-nav-link>
                        </li>
                    @endif
                    <li>
                        <form method="POST" action="{{ route('logout') }}" data-submit-guard>
                            @csrf
                            <button
                                type="submit"
                                class="group inline-flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-text/80 transition-colors duration-200 hover:text-accent-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark disabled:cursor-wait disabled:opacity-70"
                            ><x-loading-label :loading-text="__('Logging out…')">Log out</x-loading-label></button>
                        </form>
                    </li>
                </ul>
            </nav>

            <x-notification-bell />

            <x-site.theme-toggle />

            {{-- Mobile menu toggle --}}
            <button
                type="button"
                x-ref="menuButton"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-controls="member-menu"
                aria-label="Toggle navigation menu"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-text transition-colors duration-200 hover:bg-text/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark sm:hidden"
            >
                <svg x-show="!open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                </svg>
                <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Mobile menu: solid surface panel below the header --}}
    <div
        id="member-menu"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="border-t border-text/15 bg-surface sm:hidden"
    >
        <nav aria-label="Member mobile" class="mx-auto max-w-7xl px-5 py-4">
            <div class="mb-3 px-4">
                <p class="font-semibold text-text">{{ $user->name }}</p>
                <p class="truncate text-sm text-text/70">{{ $user->email }}</p>
            </div>

            <ul class="flex flex-col gap-1">
                <li>
                    <a href="{{ route('home') }}" @click="open = false" class="block rounded-2xl px-4 py-3.5 text-base font-medium text-text transition-colors duration-200 hover:bg-background hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">Homepage</a>
                </li>
                <li>
                    <a href="{{ route('dashboard') }}" @click="open = false" @if (request()->routeIs('dashboard')) aria-current="page" @endif class="block rounded-2xl px-4 py-3.5 text-base font-medium text-text transition-colors duration-200 hover:bg-background hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">Dashboard</a>
                </li>
                <li>
                    <a href="{{ route('profile.edit') }}" @click="open = false" @if (request()->routeIs('profile.*')) aria-current="page" @endif class="block rounded-2xl px-4 py-3.5 text-base font-medium text-text transition-colors duration-200 hover:bg-background hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">Profile</a>
                </li>
                @if ($canSeePanel)
                    <li>
                        <a href="{{ $panel->getUrl() }}" @click="open = false" class="block rounded-2xl px-4 py-3.5 text-base font-medium text-text transition-colors duration-200 hover:bg-background hover:text-primary-dark focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark">Admin panel</a>
                    </li>
                @endif
                <li>
                    <form method="POST" action="{{ route('logout') }}" data-submit-guard>
                        @csrf
                        <button
                            type="submit"
                            class="group block w-full rounded-2xl px-4 py-3.5 text-left text-base font-medium text-text transition-colors duration-200 hover:bg-background hover:text-accent-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark disabled:cursor-wait disabled:opacity-70"
                        ><x-loading-label :loading-text="__('Logging out…')">Log out</x-loading-label></button>
                    </form>
                </li>
            </ul>
        </nav>
    </div>
</header>
