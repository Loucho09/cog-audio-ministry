<?php

namespace App\Policies;

use App\Models\MemberUnavailability;
use App\Models\User;

class MemberUnavailabilityPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('member') && ! $user->isAdminRole();
    }

    public function delete(User $user, MemberUnavailability $entry): bool
    {
        return (int) $entry->user_id === (int) $user->id;
    }
}
