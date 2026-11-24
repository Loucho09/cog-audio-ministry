<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;

/**
 * A Policy answers "may this user do this action on this record?".
 * Laravel finds it automatically (Service -> ServicePolicy) and calls it
 * from $user->can('update', $service) or $this->authorize(...).
 * Super-admins pass everything via Gate::before (AppServiceProvider).
 */
class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasAnyRole(['branch-head', 'branch-admin']);
    }

    public function view(User $user, Service $service): bool
    {
        return $this->viewAny($user) && $service->belongsToBranch($user->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-head');
    }

    public function update(User $user, Service $service): bool
    {
        return $this->create($user) && $service->belongsToBranch($user->branch_id);
    }

    public function delete(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }
}
