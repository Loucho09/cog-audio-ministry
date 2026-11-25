@php
    // Same text the controller sends for a bad or expired token, so we can show a friendlier box instead.
    $expiredText = __('This link has expired or was replaced by a newer one. Please request a new link, or ask your branch head.');
    $emailErrors = collect($errors->get('email'));
    $linkExpired = $emailErrors->contains($expiredText);
    $otherEmailErrors = $emailErrors->reject(fn ($message) => $message === $expiredText)->values()->all();
@endphp

<x-guest-layout :title="$isInvite ? __('Set your password') : __('Choose a new password')">
    <p class="mb-5 text-sm text-text/80">
        @if ($isInvite)
            @if ($firstName)
                {{ __('Hi :name, your application was approved. Choose a password to finish setting up your member account.', ['name' => $firstName]) }}
            @else
                {{ __('Your application was approved. Choose a password to finish setting up your member account.') }}
            @endif
        @else
            {{ __('Enter a new password for your account.') }}
        @endif
    </p>

    @if ($linkExpired)
        <div role="alert" class="mb-5 rounded-xl border border-accent-text/40 bg-accent-text/10 px-4 py-3 text-sm text-text">
            <p class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-accent-text" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <span>
                    @if ($isInvite)
                        {{ __('This link has expired or was already used. Ask your branch head or the super-admin to send a new one, or use Forgot password.') }}
                    @else
                        {{ __('This link has expired or was already used.') }}
                    @endif
                </span>
            </p>
            @if (Route::has('password.request'))
                <a class="mt-2 ml-8 inline-flex min-h-11 items-center rounded-md text-sm font-medium text-primary-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark" href="{{ route('password.request') }}">
                    {{ $isInvite ? __('Forgot password') : __('Request a new reset link') }}
                </a>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" data-submit-guard x-data="{ pw: '', confirm: '' }">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address: comes from the link, so it is read-only (still submitted) -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 cursor-default bg-background text-text/80" type="email" name="email" :value="old('email', $request->email)" required readonly autocomplete="username" aria-describedby="email-note" />
            <p id="email-note" class="mt-1 text-xs text-text/70">{{ __('This email comes from your link.') }}</p>
            <x-input-error :messages="$otherEmailErrors" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="$isInvite ? __('Password') : __('New password')" />
            <x-password-input id="password" name="password" required autofocus x-model="pw" aria-describedby="password-hint" />
            <p id="password-hint" class="mt-1 text-xs text-text/70">{{ __('At least 10 characters, with letters and numbers.') }}</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm password')" />
            <x-password-input id="password_confirmation" name="password_confirmation" required x-model="confirm" aria-describedby="match-hint" />
            <p id="match-hint" aria-live="polite" class="mt-1 min-h-5 text-xs">
                <span x-show="confirm.length > 0 && pw === confirm" x-cloak class="text-text/80">{{ __('Passwords match') }}</span>
                <span x-show="confirm.length > 0 && pw !== confirm" x-cloak class="text-accent-text">{{ __("Passwords don't match yet") }}</span>
            </p>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button class="w-full sm:w-auto" :loading-text="__('Saving…')">
                {{ $isInvite ? __('Save password and continue') : __('Save new password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
