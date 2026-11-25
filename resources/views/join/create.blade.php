@php
    $field = 'mt-1 block w-full min-h-11 rounded-lg border border-text/25 bg-surface px-3 py-2 text-base text-text placeholder:text-text/50 focus:border-primary-dark focus:outline-none focus:ring-2 focus:ring-primary-dark';
    $label = 'block text-sm font-semibold text-text';
    $hint = 'mt-1 text-sm text-text/70';
@endphp

<x-public-layout
    title="Join the Team"
    description="Want to serve in the sound, stage, or livestream team? Tell us about yourself and choose a branch. We would love to hear from you."
    :transparent-nav="false"
>
    <section class="mx-auto w-full max-w-2xl px-4 pb-16 pt-28 sm:px-6">
        <h1 class="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Join the Audio Ministry</h1>
        <p class="mt-3 text-base text-text/80">
            Thank you for your heart to serve. Fill in the form below and the branch you choose will get back to you.
            Fields marked * are required.
        </p>

        @if ($errors->any())
            <div role="alert" class="mt-6 rounded-lg border border-accent-text/40 bg-surface p-4 text-sm text-accent-text">
                Please check the form. Some answers need your attention.
            </div>
        @endif

        <form method="POST" action="{{ route('join.store') }}" class="mt-8 space-y-6" novalidate>
            @csrf

            {{-- Honeypot: hidden from people and screen readers; bots tend to fill it. --}}
            <div class="hidden">
                <label for="website">Leave this field empty</label>
                <input type="text" id="website" name="website" value="" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="full_name" class="{{ $label }}">Full name *</label>
                <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="120" autocomplete="name" class="{{ $field }}" @error('full_name') aria-invalid="true" aria-describedby="full_name-error" @enderror>
                <x-input-error id="full_name-error" :messages="$errors->get('full_name')" class="mt-1" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div>
                    <label for="age" class="{{ $label }}">Age *</label>
                    <input type="number" inputmode="numeric" id="age" name="age" value="{{ old('age') }}" required min="1" max="120" class="{{ $field }}" @error('age') aria-invalid="true" aria-describedby="age-error" @enderror>
                    <x-input-error id="age-error" :messages="$errors->get('age')" class="mt-1" />
                </div>
                <div>
                    <label for="contact_number" class="{{ $label }}">Contact number *</label>
                    <input type="tel" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required maxlength="30" autocomplete="tel" class="{{ $field }}" @error('contact_number') aria-invalid="true" aria-describedby="contact_number-error" @enderror>
                    <x-input-error id="contact_number-error" :messages="$errors->get('contact_number')" class="mt-1" />
                </div>
            </div>

            <div>
                <label for="email" class="{{ $label }}">Email *</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" class="{{ $field }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                <x-input-error id="email-error" :messages="$errors->get('email')" class="mt-1" />
            </div>

            <div>
                <label for="facebook_url" class="{{ $label }}">Facebook profile link</label>
                <input type="url" id="facebook_url" name="facebook_url" value="{{ old('facebook_url') }}" maxlength="255" placeholder="https://facebook.com/..." class="{{ $field }}" aria-describedby="facebook_url-hint{{ $errors->has('facebook_url') ? ' facebook_url-error' : '' }}" @error('facebook_url') aria-invalid="true" @enderror>
                <p id="facebook_url-hint" class="{{ $hint }}">Optional.</p>
                <x-input-error id="facebook_url-error" :messages="$errors->get('facebook_url')" class="mt-1" />
            </div>

            <div>
                <label for="branch_id" class="{{ $label }}">Branch *</label>
                <select id="branch_id" name="branch_id" required class="{{ $field }}" @error('branch_id') aria-invalid="true" aria-describedby="branch_id-error" @enderror>
                    <option value="">Choose a branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                    @endforeach
                </select>
                <x-input-error id="branch_id-error" :messages="$errors->get('branch_id')" class="mt-1" />
            </div>

            <div>
                <label for="consent" class="flex cursor-pointer items-start gap-3 text-base text-text">
                    <input type="checkbox" id="consent" name="consent" value="1" required @checked(old('consent')) class="mt-1 size-5 shrink-0 rounded border-text/40 text-primary-dark focus:ring-2 focus:ring-primary-dark" @error('consent') aria-invalid="true" aria-describedby="consent-error" @enderror>
                    <span>I agree that the ministry may contact me about my application using the details above. *</span>
                </label>
                <x-input-error id="consent-error" :messages="$errors->get('consent')" class="mt-1" />
            </div>

            <x-site.button type="submit" size="lg" class="w-full sm:w-auto">Send my application</x-site.button>
        </form>
    </section>
</x-public-layout>
