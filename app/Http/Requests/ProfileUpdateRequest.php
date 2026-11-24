<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    private function emailChanged(): bool
    {
        return mb_strtolower((string) $this->input('email')) !== mb_strtolower($this->user()->email);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Only needed when the email changes, so a stolen session cannot silently swap it.
            'current_password' => [
                Rule::requiredIf($this->emailChanged()),
                'nullable',
                Rule::when($this->emailChanged(), ['current_password']),
            ],
        ];
    }
}
