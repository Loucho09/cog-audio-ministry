<?php

use App\Models\Branch;
use App\Models\User;
use Spatie\Permission\Models\Role;

it('converts branch-heads to branch-admins and deletes the role, ignoring the admin limit', function () {
    $branch = Branch::factory()->create(['slug' => 'trece']);
    $head = User::factory()->create(['branch_id' => $branch->id]);
    $admin = User::factory()->create(['branch_id' => $branch->id]);
    Role::findOrCreate('branch-admin', 'web');
    $head->assignRole(Role::findOrCreate('branch-head', 'web'));
    $admin->assignRole('branch-admin');

    (require database_path('migrations/2026_11_26_120000_remove_branch_head_role.php'))->up();

    expect($head->fresh()->getRoleNames()->all())->toBe(['branch-admin'])
        ->and($admin->fresh()->hasRole('branch-admin'))->toBeTrue()
        ->and(Role::where('name', 'branch-head')->exists())->toBeFalse();
});
