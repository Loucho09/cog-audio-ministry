<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JoinApplication;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/** Fake demo applications (local/testing only). Skips branches that already have some, so re-seeding does not duplicate. */
class JoinApplicationSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        foreach (['super-admin', 'branch-head', 'member'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        foreach (Branch::all() as $branch) {
            if (JoinApplication::where('branch_id', $branch->id)->exists()) {
                continue;
            }

            $reviewer = $this->reviewerFor($branch);

            JoinApplication::factory()->pending()->count(2)->create(['branch_id' => $branch->id]);
            JoinApplication::factory()->rejected($reviewer)->create(['branch_id' => $branch->id]);

            $approved = JoinApplication::factory()->approved($reviewer)->create(['branch_id' => $branch->id]);
            $member = User::create([
                'name' => $approved->full_name,
                'email' => $approved->email,
                'password' => Hash::make(Str::random(40)),
            ]);
            $member->forceFill(['branch_id' => $branch->id, 'email_verified_at' => now()])->save();
            $member->assignRole('member');
            $approved->forceFill(['user_id' => $member->id])->save();
        }
    }

    /** The branch's head if DemoUserSeeder made one, otherwise a super-admin. */
    private function reviewerFor(Branch $branch): ?User
    {
        return User::role('branch-head')->where('branch_id', $branch->id)->first()
            ?? User::role('super-admin')->first();
    }
}
