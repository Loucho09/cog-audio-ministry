<?php

namespace App\Services;

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;
use App\Notifications\DutyReminder;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/** Sends the "tomorrow" and "soon" reminders for accepted member duties. Safe to run often and overlapped. */
class DutyReminderService
{
    /** @return int number of reminders sent */
    public function run(): int
    {
        $now = now();
        $soonEnd = $now->copy()->addHours((int) config('ministry.reminders.soon_hours', 2));
        $dayEnd = $now->copy()->addHours((int) config('ministry.reminders.day_before_hours', 24));

        return $this->process($now, $soonEnd, 'reminded_soon_at', DutyReminder::SOON)
            + $this->process($soonEnd, $dayEnd, 'reminded_day_at', DutyReminder::DAY_BEFORE);
    }

    /** Window is (from, to]. A "soon" reminder also marks the day reminder done so a late accept gets only one. */
    private function process(CarbonInterface $from, CarbonInterface $to, string $column, string $kind): int
    {
        $assignments = DutyAssignment::query()->withoutGlobalScopes()
            ->whereNotNull('user_id')
            ->where('status', DutyStatus::Accepted->value)
            ->whereNull($column)
            ->whereHas('event', fn (Builder $q) => $q
                ->where('starts_at', '>', $from)
                ->where('starts_at', '<=', $to))
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($assignments as $assignment) {
            try {
                // Claim the row first so an overlapping run cannot send the same reminder twice.
                $values = [$column => now()];
                if ($kind === DutyReminder::SOON) {
                    $values['reminded_day_at'] = $assignment->reminded_day_at ?? now();
                }

                $claimed = DutyAssignment::query()->withoutGlobalScopes()
                    ->whereKey($assignment->id)->whereNull($column)->update($values);

                if ($claimed !== 1 || $assignment->user === null) {
                    continue;
                }

                $assignment->user->notify(new DutyReminder($assignment, $kind));
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
