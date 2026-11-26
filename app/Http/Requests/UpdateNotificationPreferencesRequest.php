<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    /**
     * An unchecked checkbox is simply absent, which means "off".
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['email_notifications' => $this->boolean('email_notifications')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email_notifications' => ['required', 'boolean'],
        ];
    }
}
