<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

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

        if (
            $role !== 'super-admin'
            && $target->hasRole('super-admin')
            && User::role('super-admin')->count() <= 1
        ) {
            throw new AuthorizationException('Cannot remove the last super-admin.');
        }

        $target->syncRoles([$role]);

        return $target;
    }
}
