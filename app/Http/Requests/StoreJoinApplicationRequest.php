<?php

namespace App\Http\Requests;

use App\Models\JoinApplication;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreJoinApplicationRequest extends FormRequest
{
    public const DUPLICATE_MESSAGE = 'We could not accept an application with this email. If you have already applied or have an account, please wait for our reply or contact us.';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Honeypot first: bots get the thank-you page before any validation or database lookup.
        if ($this->isBot()) {
            throw new HttpResponseException(redirect()->route('join.thanks'));
        }

        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'full_name' => trim((string) $this->input('full_name')),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:120'],
            'age' => ['required', 'integer', 'min:1', 'max:120'],
            'contact_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{7,30}$/'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'facebook_url' => ['nullable', 'url:https', 'max:255'],
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'consent' => ['accepted'],
            'website' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'consent.accepted' => 'Please tick the box to let us contact you about your application.',
            'branch_id.required' => 'Please choose a branch.',
            'branch_id.exists' => 'Please choose a branch from the list.',
        ];
    }

    /** One generic message for "already a member" and "already pending" so neither case is revealed. */
    public function after(): array
    {
        return [function ($validator) {
            if ($validator->errors()->has('email')) {
                return;
            }

            if (self::emailTaken($this->input('email'))) {
                $validator->errors()->add('email', self::DUPLICATE_MESSAGE);
            }
        }];
    }

    /** Email is stored lowercase and MySQL collation is case-insensitive, so a plain match is enough. */
    public static function emailTaken(string $email): bool
    {
        return User::where('email', $email)->exists()
            || JoinApplication::pending()->where('email', $email)->exists();
    }

    public function isBot(): bool
    {
        return filled($this->input('website'));
    }
}
