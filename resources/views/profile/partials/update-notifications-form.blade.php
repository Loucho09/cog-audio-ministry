<section>
    <header>
        <h2 class="text-lg font-semibold text-text">
            {{ __('Notifications') }}
        </h2>

        <p class="mt-1 text-sm text-text/70">
            {{ __('Choose whether we also send duty updates to your email.') }}
        </p>
    </header>

    <form method="post" action="{{ route('profile.notifications.update') }}" class="mt-6 space-y-5" data-submit-guard>
        @csrf
        @method('patch')

        <label for="email_notifications" class="flex min-h-11 cursor-pointer items-start gap-3">
            <span class="relative mt-0.5 inline-flex h-6 w-11 shrink-0">
                <input
                    type="checkbox"
                    role="switch"
                    id="email_notifications"
                    name="email_notifications"
                    value="1"
                    aria-describedby="email_notifications_help"
                    @checked(old('email_notifications', $user->email_notifications))
                    class="peer sr-only"
                >
                <span aria-hidden="true" class="pointer-events-none absolute inset-0 rounded-full bg-text/50 transition-colors motion-reduce:transition-none peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-surface"></span>
                <span aria-hidden="true" class="pointer-events-none absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition-transform motion-reduce:transition-none peer-checked:translate-x-5"></span>
            </span>
            <span>
                <span class="block text-sm font-medium text-text">{{ __('Email me about my duties (new assignments, swaps, reminders)') }}</span>
                <span id="email_notifications_help" class="mt-1 block text-sm text-text/70">{{ __('You will always see notifications in the app (the bell).') }}</span>
            </span>
        </label>
        <x-input-error :messages="$errors->get('email_notifications')" class="mt-2" />

        <div class="flex flex-wrap items-center gap-4">
            <x-primary-button :loading-text="__('Saving…')">{{ __('Save') }}</x-primary-button>

            <div role="status" aria-live="polite">
                @if (session('status') === 'notifications-updated')
                    <p
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-text/70"
                    >{{ __('Saved.') }}</p>
                @endif
            </div>
        </div>
    </form>
</section>
