@php
    // Laravel's LoginRequest sends the throttle text under the "email" key. Find it by matching the
    // auth.throttle translation (placeholders become \d+), so the message text source is unchanged.
    $emailErrors = collect($errors->get('email'));
    $throttleTemplate = __('auth.throttle', ['seconds' => '__S__', 'minutes' => '__M__']);
    $throttlePattern = '/^'.str_replace(['__S__', '__M__'], ['\d+', '\d+'], preg_quote($throttleTemplate, '/')).'$/u';
    $throttleMessage = $emailErrors->first(fn ($message) => preg_match($throttlePattern, $message));
    $retrySeconds = ($throttleMessage && preg_match('/\d+/', $throttleMessage, $matches)) ? (int) $matches[0] : 0;
    $emailFieldErrors = $emailErrors->reject(fn ($message) => $message === $throttleMessage)->values()->all();
@endphp

<x-guest-layout :title="__('Log in')" :split="true">
    <!-- Session Status (success alert) -->
    @if (session('status'))
        <div role="status" class="mb-5 flex items-start gap-3 rounded-xl border border-primary-dark/40 bg-primary-dark/10 px-4 py-3 text-sm font-medium text-text">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary-dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Throttle alert: shown above the form instead of under the email field -->
    @if ($throttleMessage)
        <div
            role="alert"
            class="mb-5 rounded-xl border border-accent-text/40 bg-accent-text/10 px-4 py-3 text-sm text-text"
            x-data="{ remaining: {{ $retrySeconds }}, timer: null }"
            x-init="timer = setInterval(() => { if (remaining > 0) { remaining--; } if (remaining <= 0) { clearInterval(timer); } }, 1000)"
        >
            <p class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-accent-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>{{ $throttleMessage }}</span>
            </p>
            @if ($retrySeconds > 0)
                <p class="mt-2 pl-8 text-xs text-text/70" x-show="remaining > 0" x-cloak>
                    Time left: <span x-text="remaining">{{ $retrySeconds }}</span>s
                </p>
            @endif
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('login') }}"
        class="space-y-5"
        x-data="{ showPassword: false, hasPassword: false, capsLock: false, submitting: false }"
        @submit="if (submitting) { $event.preventDefault(); } else { submitting = true; }"
        @pageshow.window="submitting = false"
    >
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$emailFieldErrors" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <div class="relative mt-1">
                <x-text-input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    class="pr-12"
                    x-bind:type="showPassword ? 'text' : 'password'"
                    x-init="hasPassword = $el.value.length > 0"
                    x-on:input="hasPassword = $el.value.length > 0; if (!hasPassword) { showPassword = false; }"
                    aria-describedby="caps-lock-warning"
                    x-on:keydown="capsLock = $event.getModifierState('CapsLock')"
                    x-on:keyup="capsLock = $event.getModifierState('CapsLock')"
                    x-on:blur="capsLock = false"
                />
                <button
                    type="button"
                    class="absolute inset-y-0 right-0 my-auto mr-1 inline-flex h-11 w-11 items-center justify-center rounded-lg text-text/70 transition-opacity duration-200 hover:text-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark motion-reduce:transition-none"
                    x-bind:class="hasPassword ? 'opacity-100' : 'pointer-events-none opacity-0'"
                    x-bind:disabled="!hasPassword"
                    x-bind:tabindex="hasPassword ? 0 : -1"
                    x-on:click="showPassword = !showPassword"
                    x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                    x-bind:aria-pressed="showPassword.toString()"
                >
                    <svg x-show="!showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg x-show="showPassword" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>

            <!-- Caps Lock warning: the live region stays in the DOM so screen readers announce changes -->
            <div aria-live="polite" class="mt-2">
                <p id="caps-lock-warning" x-show="capsLock" x-cloak class="flex items-center gap-2 text-sm text-accent-text">
                    <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                    Caps Lock is on.
                </p>
            </div>
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

            <x-primary-button class="w-full sm:w-auto disabled:cursor-not-allowed disabled:opacity-70" x-bind:disabled="submitting" x-bind:aria-busy="submitting.toString()">
                <span x-show="!submitting">{{ __('Log in') }}</span>
                <span x-show="submitting" x-cloak class="inline-flex items-center gap-2">
                    <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
                    </svg>
                    Logging in…
                </span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
