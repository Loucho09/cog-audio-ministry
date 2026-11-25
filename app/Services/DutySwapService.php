<?php

namespace App\Services;

use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\MemberUnavailability;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DutySwapService
{
    public function __construct(private DutyRosterService $roster) {}

    public function offer(DutyAssignment $assignment, User $user): DutySwap
    {
        return DB::transaction(function () use ($assignment, $user) {
            $current = DutyAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($current->user_id === null || (int) $current->user_id !== (int) $user->id) {
                throw new RosterRuleViolation('This duty is not yours to swap.');
            }

            if ($current->status !== DutyStatus::Accepted) {
                throw new RosterRuleViolation('Accept the duty first, or decline it.');
            }

            if ($current->event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so the duty can no longer be swapped.');
            }

            if ($current->liveSwap()) {
                throw new RosterRuleViolation('This duty already has a swap offer.');
            }

            $current->update(['status' => DutyStatus::SwapRequested]);

            return DutySwap::create([
                'branch_id' => $current->branch_id,
                'duty_assignment_id' => $current->id,
                'offered_by' => $user->id,
                'status' => SwapStatus::Open,
            ]);
        });
    }

    public function cancel(DutySwap $swap, User $user): DutySwap
    {
        return DB::transaction(function () use ($swap, $user) {
            // Lock order: swap, then assignment.
            $swap = DutySwap::query()->whereKey($swap->id)->lockForUpdate()->firstOrFail();
            $assignment = DutyAssignment::query()->whereKey($swap->duty_assignment_id)->lockForUpdate()->firstOrFail();

            if ((int) $swap->offered_by !== (int) $user->id) {
                throw new RosterRuleViolation('Only the member who offered this swap can cancel it.');
            }

            if (! in_array($swap->status, [SwapStatus::Open, SwapStatus::Claimed], true) || $assignment->event->hasStarted()) {
                throw new RosterRuleViolation('This swap can no longer be cancelled.');
            }

            $swap->update(['status' => SwapStatus::Cancelled]);
            $assignment->update(['status' => DutyStatus::Accepted]);

            return $swap;
        });
    }

    public function claim(DutySwap $swap, User $user): DutySwap
    {
        return DB::transaction(function () use ($swap, $user) {
            // Lock order: swap, then user (the assignment is only read).
            $swap = DutySwap::query()->whereKey($swap->id)->lockForUpdate()->firstOrFail();
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $event = $swap->assignment->event;

            if ($swap->status === SwapStatus::Claimed) {
                throw new RosterRuleViolation('Someone already took this swap.');
            }

            if ($swap->status !== SwapStatus::Open) {
                throw new RosterRuleViolation('This swap is no longer available.');
            }

            if ($event->hasStarted()) {
                throw new RosterRuleViolation('This event has already started, so the swap is closed.');
            }

            if ((int) $swap->offered_by === (int) $user->id) {
                throw new RosterRuleViolation('You cannot take your own swap.');
            }

            if (! $user->hasAnyRole(['member', 'branch-admin'])) {
                throw new RosterRuleViolation('This person cannot be given duties.');
            }

            if ((int) $user->branch_id !== (int) $event->branch_id) {
                throw new RosterRuleViolation('This swap belongs to a different branch.');
            }

            if ($this->roster->isServingDuring($user, $event)) {
                throw new RosterRuleViolation('You are already serving at that time.');
            }

            if (MemberUnavailability::blocks($user, $event)) {
                throw new RosterRuleViolation('You marked this time as unavailable.');
            }

            $swap->update([
                'status' => SwapStatus::Claimed,
                'claimed_by' => $user->id,
                'claimed_at' => now(),
            ]);

            return $swap;
        });
    }
}
