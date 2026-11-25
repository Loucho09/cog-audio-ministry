<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JoinApplication> */
class JoinApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'full_name' => fake()->name(),
            'age' => fake()->numberBetween(15, 45),
            'contact_number' => '09'.fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'facebook_url' => null,
            'interests' => fake()->randomElements(JoinApplication::INTERESTS, fake()->numberBetween(1, 3)),
            'experience' => fake()->sentence(),
            'availability' => fake()->sentence(),
            'consented_at' => now(),
        ];
    }

    // status and review columns are guarded, so states set them with forceFill.
    public function pending(): static
    {
        return $this->afterMaking(fn (JoinApplication $a) => $a->forceFill(['status' => JoinApplication::STATUS_PENDING]));
    }

    public function approved(): static
    {
        return $this->reviewed(JoinApplication::STATUS_APPROVED);
    }

    public function rejected(): static
    {
        return $this->reviewed(JoinApplication::STATUS_REJECTED);
    }

    // make() creates no rows; the reviewer user is only created on create().
    private function reviewed(string $status): static
    {
        return $this
            ->afterMaking(fn (JoinApplication $a) => $a->forceFill(['status' => $status, 'reviewed_at' => now()]))
            ->afterCreating(fn (JoinApplication $a) => $a->forceFill(['reviewed_by' => User::factory()->create()->id])->save());
    }
}
