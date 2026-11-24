<?php

namespace App\Policies;

use App\Models\Faq;
use App\Models\User;

/** FAQs are global: staff may read, only super-admin (via Gate::before) may change. */
class FaqPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->branch_id !== null && $user->hasAnyRole(['branch-head', 'branch-admin']);
    }

    public function view(User $user, Faq $faq): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Faq $faq): bool
    {
        return false;
    }

    public function delete(User $user, Faq $faq): bool
    {
        return false;
    }

    public function restore(User $user, Faq $faq): bool
    {
        return false;
    }

    public function forceDelete(User $user, Faq $faq): bool
    {
        return false;
    }
}
