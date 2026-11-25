<?php

namespace App\Policies;

use App\Models\DutyAssignment;
use App\Models\User;

class DutyAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function view(User $user, DutyAssignment $dutyAssignment): bool
    {
        return $this->viewAny($user) && $dutyAssignment->belongsToBranch($user->branch_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, DutyAssignment $dutyAssignment): bool
    {
        return $this->create($user) && $dutyAssignment->belongsToBranch($user->branch_id);
    }

    public function delete(User $user, DutyAssignment $dutyAssignment): bool
    {
        return $this->update($user, $dutyAssignment);
    }

    public function respond(User $user, DutyAssignment $dutyAssignment): bool
    {
        return $dutyAssignment->user_id === $user->id;
    }

    public function offerSwap(User $user, DutyAssignment $dutyAssignment): bool
    {
        return $dutyAssignment->user_id !== null && $dutyAssignment->user_id === $user->id;
    }
}
