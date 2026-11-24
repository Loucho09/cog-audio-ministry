<?php

namespace App\Models\Concerns;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds branch scoping to a model that has a branch_id column.
 *
 * A query scope is a reusable piece of query: Service::visibleTo($user) only
 * returns rows that user is allowed to see. Use it in admin lists so branch
 * limits are enforced in the database query, not only hidden in the UI.
 */
trait BelongsToBranch
{
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null) {
            return $query->whereKey([]);
        }

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        if ($user->branch_id !== null) {
            return $query->where($query->getModel()->getTable().'.branch_id', $user->branch_id);
        }

        // No branch and not super-admin: see nothing.
        return $query->whereKey([]);
    }

    /** True when this record belongs to the given branch (model or id). */
    public function belongsToBranch(Branch|int|null $branch): bool
    {
        $id = $branch instanceof Branch ? $branch->id : $branch;

        return $id !== null && (int) $this->branch_id === (int) $id;
    }
}
