<section>
    <header>
        <h2 class="text-lg font-semibold text-text">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-text/70">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    @if (auth()->user()->must_change_password)
        <p class="mt-6 text-sm text-text/80">
            {{ __('You are using a temporary password. Please set your own on the change password page.') }}
            <a href="{{ route('password.change') }}" class="font-semibold text-primary-dark underline">{{ __('Change password') }}</a>
        </p>
    @else
        <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
            @csrf
            @method('put')

            <div>
                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>

                @if (session('status') === 'password-updated')
                    <p
                        role="status"
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition
                        x-init="setTimeout(() => show = false, 2000)"
                        class="text-sm text-text/70"
                    >{{ __('Saved.') }}</p>
                @endif
            </div>
        </form>
    @endif
</section>
