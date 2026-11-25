<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Removes the branch-head role: every branch-head becomes a branch-admin.
 * This conversion is deliberately NOT subject to the branch admin limit.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $head = Role::query()->where('name', 'branch-head')->where('guard_name', 'web')->first();

            if ($head === null) {
                return;
            }

            $admin = Role::findOrCreate('branch-admin', 'web');

            foreach ($head->users()->get() as $user) {
                $user->assignRole($admin);
                $user->removeRole($head);
            }

            $head->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::findOrCreate('branch-head', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
