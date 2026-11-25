<?php

namespace App\Actions;

use App\Models\JoinApplication;
use App\Models\User;
use App\Notifications\NewJoinApplication;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Throwable;

class NotifyStaffOfJoinApplication
{
    /**
     * Branch-admin users of the application's branch, plus all super-admins.
     * One row per user, even when they hold several of these roles.
     *
     * @return Collection<int, User>
     */
    public function recipients(JoinApplication $application): Collection
    {
        return User::query()
            ->where(function ($query) use ($application) {
                $query->whereHas('roles', fn ($roles) => $roles->where('name', 'super-admin'))
                    ->orWhere(function ($query) use ($application) {
                        $query->where('branch_id', $application->branch_id)
                            ->whereHas('roles', fn ($roles) => $roles->where('name', 'branch-admin'));
                    });
            })
            ->get();
    }

    public function handle(JoinApplication $application): void
    {
        try {
            $recipients = $this->recipients($application);

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new NewJoinApplication($application));
        } catch (Throwable $e) {
            // Never break the applicant's redirect because staff could not be notified.
            report($e);
        }
    }
}
