<x-guest-layout :title="__('Verify email')">
    <p class="text-sm leading-relaxed text-text/70">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <x-auth-session-status class="mt-4" :status="__('A new verification link has been sent to the email address you provided during registration.')" />
    @endif

    <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button class="w-full sm:w-auto">
                {{ __('Resend Verification Email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-md px-3 text-sm font-medium text-primary-dark underline-offset-4 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-dark sm:w-auto">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
