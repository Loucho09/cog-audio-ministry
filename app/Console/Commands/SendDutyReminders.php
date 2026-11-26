<?php

namespace App\Console\Commands;

use App\Services\DutyReminderService;
use Illuminate\Console\Command;

class SendDutyReminders extends Command
{
    protected $signature = 'duty:send-reminders';

    protected $description = 'Send day-before and soon reminders for accepted duties';

    public function handle(DutyReminderService $reminders): int
    {
        $this->info('Duty reminders sent: '.$reminders->run().'.');

        return self::SUCCESS;
    }
}
