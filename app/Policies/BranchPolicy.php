<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

/** Staff may see their own branch only; only super-admin (via Gate::before) may change branches. */
class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $this->viewAny($user) && (int) $branch->id === (int) $user->branch_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Branch $branch): bool
    {
        return false;
    }

    public function delete(User $user, Branch $branch): bool
    {
        return false;
    }

    public function restore(User $user, Branch $branch): bool
    {
        return false;
    }

    public function forceDelete(User $user, Branch $branch): bool
    {
        return false;
    }
}
