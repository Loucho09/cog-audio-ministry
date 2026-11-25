<?php

namespace App\Policies;

use App\Models\TeamMember;
use App\Models\User;

/** Branch-scoped content; records with a null branch_id are global (super-admin only to change). */
class TeamMemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function view(User $user, TeamMember $teamMember): bool
    {
        return $this->viewAny($user) && ($teamMember->branch_id === null || $teamMember->belongsToBranch($user->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function update(User $user, TeamMember $teamMember): bool
    {
        return $this->create($user) && $teamMember->belongsToBranch($user->branch_id);
    }

    public function delete(User $user, TeamMember $teamMember): bool
    {
        return $this->update($user, $teamMember);
    }

    public function restore(User $user, TeamMember $teamMember): bool
    {
        return false;
    }

    public function forceDelete(User $user, TeamMember $teamMember): bool
    {
        return false;
    }
}
