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

    // Pass a real staff reviewer for demo data; without one (tests) a throwaway user is created.
    public function approved(?User $reviewer = null): static
    {
        return $this->reviewed(JoinApplication::STATUS_APPROVED, $reviewer);
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->reviewed(JoinApplication::STATUS_REJECTED, $reviewer);
    }

    // make() creates no rows; the fallback reviewer is only created on create().
    private function reviewed(string $status, ?User $reviewer): static
    {
        return $this
            ->afterMaking(fn (JoinApplication $a) => $a->forceFill(['status' => $status, 'reviewed_at' => now()]))
            ->afterCreating(fn (JoinApplication $a) => $a->forceFill(['reviewed_by' => $reviewer?->id ?? User::factory()->create()->id])->save());
    }
}
