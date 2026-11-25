<x-guest-layout :title="__('Change your password')">
    <div class="mb-4 text-sm text-text/70">
        {{ __('You are using a temporary password. Please choose your own new password to continue.') }}
    </div>

    <form method="POST" action="{{ route('password.change.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="current_password" :value="__('Temporary password')" />
            <x-password-input id="current_password" name="current_password" autocomplete="current-password" required autofocus />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('New password')" />
            <x-password-input id="password" name="password" required />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-password-input id="password_confirmation" name="password_confirmation" required />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex flex-col-reverse items-stretch gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
            <button type="submit" form="logout-form" class="text-sm text-text/70 underline hover:text-text focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                {{ __('Log out') }}
            </button>
            <x-primary-button class="w-full sm:w-auto">
                {{ __('Change password') }}
            </x-primary-button>
        </div>
    </form>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>
</x-guest-layout>
