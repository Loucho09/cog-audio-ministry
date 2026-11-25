<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasAnyRole(['branch-head', 'branch-admin']);
    }

    public function view(User $user, Event $event): bool
    {
        return $this->viewAny($user) && $event->belongsToBranch($user->branch_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Event $event): bool
    {
        return $this->create($user) && $event->belongsToBranch($user->branch_id) && ! $event->hasStarted();
    }

    public function delete(User $user, Event $event): bool
    {
        return $this->update($user, $event);
    }
}
