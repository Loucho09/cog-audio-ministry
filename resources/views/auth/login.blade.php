<x-guest-layout :title="__('Log in')">
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div>
            <label for="remember_me" class="inline-flex min-h-11 cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" class="h-5 w-5 rounded border-text/50 bg-surface text-primary-dark shadow-none focus:outline-none focus:ring-2 focus:ring-primary-dark focus:ring-offset-0" name="remember">
                <span class="text-sm text-text/80">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div class="flex flex-col-reverse gap-4 pt-2 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="inline-flex min-h-11 items-center justify-center rounded-md px-3 text-sm font-medium text-primary-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="w-full sm:w-auto">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
