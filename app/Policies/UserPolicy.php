<?php

namespace App\Policies;

use App\Models\User;

/**
 * Who may manage which user accounts. A branch-head manages only plain
 * members of their own branch, never admins and never themselves.
 * Role changes are NOT allowed here; use App\Actions\AssignRole (super-admin only).
 * Super-admins pass everything via Gate::before (AppServiceProvider).
 */
class UserPolicy
{
    private const ADMIN_ROLES = ['super-admin', 'branch-head', 'branch-admin'];

    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasAnyRole(['branch-head', 'branch-admin']);
    }

    /**
     * Branch-heads view the members they manage. Branch-admins may view
     * (never edit) plain members of their own branch, for the duty roster.
     */
    public function view(User $user, User $target): bool
    {
        if ($this->manages($user, $target)) {
            return true;
        }

        return $user->branch_id !== null
            && $user->hasRole('branch-admin')
            && ! $user->is($target)
            && (int) $target->branch_id === (int) $user->branch_id
            && ! $target->hasAnyRole(self::ADMIN_ROLES);
    }

    public function update(User $user, User $target): bool
    {
        return $this->manages($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        return $this->manages($user, $target);
    }

    public function assignRole(User $user, User $target): bool
    {
        return false;
    }

    private function manages(User $user, User $target): bool
    {
        return $user->branch_id !== null
            && $user->hasRole('branch-head')
            && ! $user->is($target)
            && (int) $target->branch_id === (int) $user->branch_id
            && ! $target->hasAnyRole(self::ADMIN_ROLES);
    }
}
