<?php

namespace App\Http\Requests;

use App\Enums\AvailabilityPart;
use App\Models\MemberUnavailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class StoreAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MemberUnavailability::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.Carbon::now(config('app.timezone'))->startOfDay()->addMonths(6)->toDateString()],
            'part' => ['required', Rule::enum(AvailabilityPart::class)],
            'note' => ['nullable', 'string', 'max:100'],
        ];
    }
}
