<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->withRole('super-admin');
    }

    public function branchAdmin(Branch $branch): static
    {
        return $this->withRole('branch-admin', $branch);
    }

    public function member(Branch $branch): static
    {
        return $this->withRole('member', $branch);
    }

    private function withRole(string $role, ?Branch $branch = null): static
    {
        $factory = $this->afterCreating(function (User $user) use ($role) {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        });

        return $branch ? $factory->state(fn () => ['branch_id' => $branch->id]) : $factory;
    }
}
