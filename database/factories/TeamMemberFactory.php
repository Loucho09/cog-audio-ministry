<?php

namespace Database\Factories;

use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TeamMember> */
class TeamMemberFactory extends Factory
{
    // For tests only. Never used by seeders (no invented people).
    public function definition(): array
    {
        return ['name' => fake()->name(), 'is_published' => true];
    }
}
