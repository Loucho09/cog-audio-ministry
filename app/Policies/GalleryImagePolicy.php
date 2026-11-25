<?php

namespace App\Policies;

use App\Models\GalleryImage;
use App\Models\User;

/** Branch-scoped content; records with a null branch_id are global (super-admin only to change). */
class GalleryImagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function view(User $user, GalleryImage $image): bool
    {
        return $this->viewAny($user) && ($image->branch_id === null || $image->belongsToBranch($user->branch_id));
    }

    public function create(User $user): bool
    {
        return $user->branch_id !== null && $user->hasRole('branch-admin');
    }

    public function update(User $user, GalleryImage $image): bool
    {
        return $this->create($user) && $image->belongsToBranch($user->branch_id);
    }

    public function delete(User $user, GalleryImage $image): bool
    {
        return $this->update($user, $image);
    }

    public function restore(User $user, GalleryImage $image): bool
    {
        return false;
    }

    public function forceDelete(User $user, GalleryImage $image): bool
    {
        return false;
    }
}
