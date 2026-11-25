<?php

namespace App\Policies;

use App\Models\DutySwap;
use App\Models\User;

class DutySwapPolicy
{
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
