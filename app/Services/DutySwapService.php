<?php

namespace App\Services;

use App\Enums\DutyStatus;
use App\Enums\SwapStatus;
use App\Exceptions\RosterRuleViolation;
use App\Models\DutyAssignment;
use App\Models\DutySwap;
use App\Models\Event;
use App\Models\MemberUnavailability;
use App\Models\User;
use App\Notifications\DutySwapUpdated;
use Illuminate\Support\Facades\DB;
use Throwable;

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

            // The assignment row lock above serialises this check: a concurrent offer waits for our commit.
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
        $swap = DB::transaction(function () use ($swap, $user) {
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

            if ($user->isAdminRole()) {
                throw new RosterRuleViolation('Admins manage the roster and cannot take swaps.');
            }

            if (! $user->hasRole('member')) {
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

        $this->notifySwap($swap, DutySwapUpdated::CLAIMED, $user);

        return $swap;
    }

    /**
     * Approves a claimed swap: the original duty becomes Swapped and the claimer gets a new Accepted duty.
     * Active slot count stays the same (Swapped is not active; the new row is).
     *
     * Lock order: swap, then event, then claimer user, then original assignment.
     * No cycle with claim (swap, user), cancel (swap, assignment), remove (swap, assignment)
     * or assign (event, user): every path takes these in the same relative order, and only
     * approve takes swap and event together.
     */
    public function approve(DutySwap $swap, User $admin): DutyAssignment
    {
        [$new, $swap] = DB::transaction(function () use ($swap, $admin) {
            $swap = DutySwap::query()->whereKey($swap->id)->lockForUpdate()->firstOrFail();
            $event = Event::query()->whereKey(DutyAssignment::query()->whereKey($swap->duty_assignment_id)->value('event_id'))->lockForUpdate()->firstOrFail();
            $claimer = $swap->claimed_by
                ? User::query()->whereKey($swap->claimed_by)->lockForUpdate()->first()
                : null;
            $assignment = DutyAssignment::query()->whereKey($swap->duty_assignment_id)->lockForUpdate()->firstOrFail();

            $this->assertDecidable($swap, $admin, $event);

            if ($swap->status !== SwapStatus::Claimed || $claimer === null) {
                throw new RosterRuleViolation('Only a swap that someone took can be approved.');
            }

            if (! $claimer->hasAnyRole(['member', 'branch-admin'])) {
                throw new RosterRuleViolation('This person cannot be given duties.');
            }

            if ((int) $claimer->branch_id !== (int) $event->branch_id) {
                throw new RosterRuleViolation('This swap belongs to a different branch.');
            }

            if ($this->roster->isServingDuring($claimer, $event)) {
                throw new RosterRuleViolation("{$claimer->name} is already serving at that time.");
            }

            if (MemberUnavailability::blocks($claimer, $event)) {
                throw new RosterRuleViolation("{$claimer->name} marked this time as unavailable.");
            }

            $assignment->update(['status' => DutyStatus::Swapped]);

            $new = DutyAssignment::create([
                'branch_id' => $assignment->branch_id,
                'event_id' => $assignment->event_id,
                'duty_role_id' => $assignment->duty_role_id,
                'user_id' => $claimer->id,
                'assigned_by' => $admin->id,
                'status' => DutyStatus::Accepted,
                'responded_at' => now(),
            ]);

            $swap->update([
                'status' => SwapStatus::Approved,
                'decided_by' => $admin->id,
                'decided_at' => now(),
            ]);

            return [$new, $swap];
        });

        $this->notifySwap($swap, DutySwapUpdated::APPROVED, $admin);

        return $new;
    }

    /** Rejects an open or claimed swap; the duty goes back to Accepted. Lock order: swap, then assignment. */
    public function reject(DutySwap $swap, User $admin): DutySwap
    {
        $swap = DB::transaction(function () use ($swap, $admin) {
            $swap = DutySwap::query()->whereKey($swap->id)->lockForUpdate()->firstOrFail();
            $assignment = DutyAssignment::query()->whereKey($swap->duty_assignment_id)->lockForUpdate()->firstOrFail();

            $this->assertDecidable($swap, $admin, $assignment->event);

            $swap->update([
                'status' => SwapStatus::Rejected,
                'decided_by' => $admin->id,
                'decided_at' => now(),
            ]);
            $assignment->update(['status' => DutyStatus::Accepted]);

            return $swap;
        });

        $this->notifySwap($swap, DutySwapUpdated::REJECTED, $admin);

        return $swap;
    }

    /**
     * Single trigger point for swap messages, called after the transaction commits.
     * Claim: branch admins + super-admins + offerer. Approve/reject: offerer + claimer (a rejected open
     * swap has no claimer). The actor is never notified about their own action.
     * A notification problem is reported, never thrown: the swap action is already committed.
     */
    private function notifySwap(DutySwap $swap, string $type, User $actor): void
    {
        try {
            $ids = $type === DutySwapUpdated::CLAIMED
                ? User::query()
                    // whereHas, not ->role(): the role scope throws if a role row does not exist.
                    ->whereHas('roles', fn ($r) => $r->where('name', 'super-admin'))
                    ->orWhere(fn ($q) => $q->where('branch_id', $swap->branch_id)
                        ->whereHas('roles', fn ($r) => $r->where('name', 'branch-admin')))
                    ->pluck('id')->push($swap->offered_by)
                : collect([$swap->offered_by, $swap->claimed_by]);

            $recipients = User::query()
                ->whereIn('id', $ids->filter()->unique()->reject(fn ($id) => (int) $id === (int) $actor->id)->values())
                ->get();

            $swap->load('assignment.event', 'assignment.dutyRole', 'offeredBy', 'claimedBy');

            foreach ($recipients as $recipient) {
                try {
                    $recipient->notify(new DutySwapUpdated($swap, $type));
                } catch (Throwable $e) {
                    report($e); // One bad recipient must not skip the others.
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Super-admin always; a branch-admin of the swap's branch, except self-approval unless they are the only admin. */
    public function canDecide(User $admin, DutySwap $swap): bool
    {
        return $this->decisionError($admin, $swap) === null;
    }

    private function decisionError(User $admin, DutySwap $swap): ?string
    {
        if ($admin->hasRole('super-admin')) {
            return null;
        }

        if (! $admin->hasRole('branch-admin') || (int) $admin->branch_id !== (int) $swap->branch_id) {
            return "You can't approve swaps for this branch.";
        }

        $involved = in_array((int) $admin->id, [(int) $swap->offered_by, (int) $swap->claimed_by], true);

        if ($involved) {
            $admins = User::query()->role('branch-admin')->where('branch_id', $swap->branch_id)->count();

            if ($admins > 1) {
                return 'Another admin of your branch needs to approve this.';
            }
        }

        return null;
    }

    private function assertDecidable(DutySwap $swap, User $admin, Event $event): void
    {
        if ($error = $this->decisionError($admin, $swap)) {
            throw new RosterRuleViolation($error);
        }

        if (! in_array($swap->status, [SwapStatus::Open, SwapStatus::Claimed], true)) {
            throw new RosterRuleViolation('This swap was already decided or cancelled.');
        }

        if ($event->hasStarted()) {
            throw new RosterRuleViolation('This swap expired because the service already started.');
        }
    }
}
