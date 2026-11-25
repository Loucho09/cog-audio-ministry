<?php

namespace App\Actions;

use App\Exceptions\JoinApplicationReviewException;
use App\Models\JoinApplication;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/** Rejects a pending application. Silent by owner decision: no email is sent. */
class RejectJoinApplication
{
    /**
     * @throws JoinApplicationReviewException
     * @throws AuthorizationException
     */
    public function handle(JoinApplication $application, User $reviewer): void
    {
        DB::transaction(function () use ($application, $reviewer): void {
            $locked = JoinApplication::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            // Branch check first (no status condition), so outsiders learn nothing about status.
            Gate::forUser($reviewer)->authorize('view', $locked);

            if (! $locked->isPending()) {
                throw new JoinApplicationReviewException('This application has already been reviewed.');
            }

            Gate::forUser($reviewer)->authorize('reject', $locked);

            $locked->forceFill([
                'status' => JoinApplication::STATUS_REJECTED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $application->setRawAttributes($locked->getAttributes(), true);
        });
    }
}
