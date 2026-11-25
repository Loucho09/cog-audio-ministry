<?php

namespace App\Policies;

use App\Models\DutyRole;
use App\Models\User;

class DutyRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasAnyRole(['branch-head', 'branch-admin']);
    }

    public function view(User $user, DutyRole $dutyRole): bool
    {
        return $this->viewAny($user) && $dutyRole->belongsToBranch($user->branch_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, DutyRole $dutyRole): bool
    {
        return $this->create($user) && $dutyRole->belongsToBranch($user->branch_id);
    }

    public function delete(User $user, DutyRole $dutyRole): bool
    {
        return $this->update($user, $dutyRole);
    }
}
