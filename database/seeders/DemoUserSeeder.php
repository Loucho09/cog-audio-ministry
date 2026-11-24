<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('DemoUserSeeder skipped: only runs in local/testing.');

            return;
        }

        $this->make('Demo Super Admin', 'superadmin@cogdasma.audio', null, 'super-admin');

        foreach (Branch::all() as $branch) {
            $this->make("Head {$branch->name}", "head@cog{$branch->slug}.audio", $branch, 'branch-head');
            $this->make("Admin {$branch->name}", "admin@cog{$branch->slug}.audio", $branch, 'branch-admin');

            for ($i = 1; $i <= 3; $i++) {
                $this->make(fake()->name(), "member{$i}@cog{$branch->slug}.audio", $branch, 'member');
            }
        }
    }

    private function make(string $name, string $email, ?Branch $branch, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make('password')],
        );

        $user->forceFill(['branch_id' => $branch?->id, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        $user->syncRoles([$role]);
    }
}
