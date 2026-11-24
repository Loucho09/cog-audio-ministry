<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The only place roles are changed. Only a super-admin may assign roles.
 */
class AssignRole
{
    public const ROLES = ['super-admin', 'branch-head', 'branch-admin', 'member'];

    /**
     * @throws AuthorizationException
     */
    public function handle(User $actor, User $target, string $role): User
    {
        if (! $actor->hasRole('super-admin')) {
            throw new AuthorizationException('Only a super-admin can assign roles.');
        }

        if (! in_array($role, self::ROLES, true)) {
            throw new AuthorizationException('Unknown role.');
        }

        if (in_array($role, ['branch-head', 'branch-admin'], true) && $target->branch_id === null) {
            throw ValidationException::withMessages([
                'role' => 'Assign the user to a branch before giving this role.',
            ]);
        }

        DB::transaction(function () use ($target, $role) {
            $superAdmins = User::role('super-admin')->lockForUpdate()->get();

            if (
                $role !== 'super-admin'
                && $superAdmins->contains('id', $target->id)
                && $superAdmins->count() <= 1
            ) {
                throw new AuthorizationException('Cannot remove the last super-admin.');
            }

            $target->syncRoles([$role]);
        });

        return $target;
    }
}
