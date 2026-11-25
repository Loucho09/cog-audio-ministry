<?php

namespace Database\Factories;

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;
use App\Models\DutyRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DutyAssignment> */
class DutyAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'branch_id' => fn (array $attrs) => Event::find($attrs['event_id'])->branch_id,
            'duty_role_id' => fn (array $attrs) => DutyRole::factory()->create([
                'branch_id' => Event::find($attrs['event_id'])->branch_id,
            ])->id,
            'user_id' => User::factory(),
            'assigned_by' => null,
            'status' => DutyStatus::Assigned,
            'responded_at' => null,
        ];
    }
}
