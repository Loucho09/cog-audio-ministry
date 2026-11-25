<?php

namespace App\Policies;

use App\Models\JoinApplication;
use App\Models\User;

/**
 * Applications are created only by the public form, so create, update and
 * delete are always false. Super-admin passes everything via Gate::before.
 */
class JoinApplicationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function view(User $user, JoinApplication $application): bool
    {
        return $this->viewAny($user) && $application->belongsToBranch($user->branch_id);
    }

    public function approve(User $user, JoinApplication $application): bool
    {
        return $this->view($user, $application) && $application->isPending();
    }

    public function reject(User $user, JoinApplication $application): bool
    {
        return $this->approve($user, $application);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, JoinApplication $application): bool
    {
        return false;
    }

    public function delete(User $user, JoinApplication $application): bool
    {
        return false;
    }
}
