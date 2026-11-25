<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Event> */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addWeek()->setTime(9, 0);

        return [
            'branch_id' => Branch::factory(),
            'service_id' => null,
            'name' => fake()->words(3, true),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHours(2),
            'notes' => null,
        ];
    }
}
