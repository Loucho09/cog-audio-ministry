<?php

namespace App\Policies;

use App\Models\DutySwap;
use App\Models\User;

class DutySwapPolicy
{
    /** Admin list: a branch-admin with a branch, or super-admin (also allowed by Gate::before). */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') || ($user->branch_id !== null && $user->hasRole('branch-admin'));
    }

    public function view(User $user, DutySwap $swap): bool
    {
        return $user->hasRole('super-admin') || ($this->viewAny($user) && $swap->belongsToBranch($user->branch_id));
    }

    /** Same-branch member or branch-admin who is not the offerer. The service enforces the rest. */
    public function claim(User $user, DutySwap $swap): bool
    {
        return (int) $swap->offered_by !== (int) $user->id
            && $user->branch_id !== null
            && $swap->belongsToBranch($user->branch_id)
            && $user->hasAnyRole(['member', 'branch-admin']);
    }

    public function cancel(User $user, DutySwap $swap): bool
    {
        return (int) $swap->offered_by === (int) $user->id;
    }
}
