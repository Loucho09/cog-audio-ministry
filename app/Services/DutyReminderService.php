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
    /** Rows that failed in the last run() (already reported and released for retry). */
    public int $failures = 0;

    /** @return int number of reminders sent */
    public function run(): int
    {
        $this->failures = 0;
        $now = now();
        $soonEnd = $now->copy()->addHours((int) config('ministry.reminders.soon_hours', 2));
        $dayEnd = $now->copy()->addHours((int) config('ministry.reminders.day_before_hours', 24));

        return $this->process($now, $soonEnd, 'reminded_soon_at', DutyReminder::SOON)
            + $this->process($soonEnd, $dayEnd, 'reminded_day_at', DutyReminder::DAY_BEFORE);
    }

    /** Window is (from, to]. A "soon" reminder also marks the day reminder done so a late accept gets only one. */
    private function process(CarbonInterface $from, CarbonInterface $to, string $column, string $kind): int
    {
        $eligible = fn () => DutyAssignment::query()
            ->whereNotNull('user_id')
            ->where('status', DutyStatus::Accepted->value)
            ->whereNull($column)
            ->whereHas('event', fn (Builder $q) => $q
                ->where('starts_at', '>', $from)
                ->where('starts_at', '<=', $to));

        $sent = 0;

        $eligible()->with(['user', 'event.branch', 'dutyRole'])->chunkById(200, function ($assignments) use (&$sent, $eligible, $column, $kind) {
            foreach ($assignments as $assignment) {
                $previousDay = $assignment->reminded_day_at;
                $claimed = false;

                try {
                    // Claim the row first (re-checking the rules) so an overlapping run cannot send twice.
                    $values = [$column => now()];
                    if ($kind === DutyReminder::SOON) {
                        $values['reminded_day_at'] = $previousDay ?? now();
                    }

                    $claimed = $eligible()->whereKey($assignment->id)->update($values) === 1;

                    if (! $claimed || $assignment->user === null) {
                        continue;
                    }

                    $assignment->user->notify(new DutyReminder($assignment, $kind));
                    $sent++;
                } catch (Throwable $e) {
                    $this->failures++;
                    report($e);

                    if ($claimed) {
                        // Release the claim so the next run retries this row.
                        $release = [$column => null];
                        if ($kind === DutyReminder::SOON) {
                            $release['reminded_day_at'] = $previousDay;
                        }
                        DutyAssignment::query()->whereKey($assignment->id)->update($release);
                    }
                }
            }
        });

        return $sent;
    }
}
