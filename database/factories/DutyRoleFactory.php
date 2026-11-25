<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\DutyRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DutyRole> */
class DutyRoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->unique()->words(2, true),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
