<?php

namespace App\Actions;

use App\Exceptions\JoinApplicationReviewException;
use App\Models\JoinApplication;
use App\Models\User;
use App\Notifications\WelcomeSetPassword;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Approves a pending application: creates the member account and queues the
 * 3-day welcome set-password email (same 'invites' broker as admin-created accounts).
 * Always grants only the `member` role; this never goes through role assignment.
 */
class ApproveJoinApplication
{
    /**
     * @throws JoinApplicationReviewException
     * @throws AuthorizationException
     */
    public function handle(JoinApplication $application, User $reviewer): User
    {
        return DB::transaction(function () use ($application, $reviewer): User {
            $locked = JoinApplication::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();

            // 1. Branch check first (no status condition), so outsiders learn nothing about status.
            Gate::forUser($reviewer)->authorize('view', $locked);

            // 2. Explicit check: Gate::before lets super-admin pass the policy even when not pending.
            if (! $locked->isPending()) {
                throw new JoinApplicationReviewException('This application has already been reviewed.');
            }

            // 3. Full approve rule.
            Gate::forUser($reviewer)->authorize('approve', $locked);

            $email = Str::lower(trim($locked->email));
            $exists = 'An account with this email already exists. Check the Users list instead.';

            if (User::query()->where('email', $email)->exists()) {
                throw new JoinApplicationReviewException($exists);
            }

            $user = new User(['name' => trim($locked->full_name), 'email' => $email]);
            // Random and never shown; the member sets their own via the welcome link.
            $user->password = Str::password(40);
            $user->branch_id = $locked->branch_id;

            try {
                $user->save();
            } catch (UniqueConstraintViolationException) {
                // Lost a race with another account creation; the transaction rolls back.
                throw new JoinApplicationReviewException($exists);
            }

            $user->assignRole('member');

            $locked->forceFill([
                'status' => JoinApplication::STATUS_APPROVED,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'user_id' => $user->id,
            ])->save();

            // Queued after commit, so a rollback never sends mail.
            $user->notify(new WelcomeSetPassword(Password::broker('invites')->createToken($user)));

            $application->setRawAttributes($locked->getAttributes(), true);

            return $user;
        });
    }
}
