<?php

namespace App\Notifications;

use App\Models\DutyAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells a member an admin gave them a duty. Always in-app; email only if they have not turned it off. */
class DutyAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public $deleteWhenMissingModels = true;

    public function __construct(public DutyAssignment $assignment)
    {
        $this->afterCommit();
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ($notifiable->email_notifications ?? true) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->details($notifiable);

        return (new MailMessage)
            ->subject('New duty: '.$d['role'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You have been given a duty. Thank you for serving.')
            ->line('Duty: '.$d['role'])
            ->line('Event: '.$d['event'])
            ->line('When: '.$d['when'])
            ->line('Where: '.$d['place'])
            ->action('Open my dashboard', route('dashboard'))
            ->line('Please open your dashboard to accept or decline.');
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $d = $this->details($notifiable);

        return [
            'type' => 'duty_assigned',
            'assignment_id' => $this->assignment->id,
            'event_id' => $this->assignment->event_id,
            'role' => $d['role'],
            'event_title' => $d['event'],
            'starts_at' => $d['starts_at'],
            'place' => $d['place'],
            'message' => "You were given {$d['role']} for {$d['event']} on {$d['when']}.",
        ];
    }

    /** @return array<string, string> */
    private function details(object $notifiable): array
    {
        $assignment = $this->assignment->loadMissing('event.branch', 'dutyRole');
        $event = $assignment->event;
        $branch = $event->branch;
        $start = $event->starts_at?->copy()->timezone(config('app.timezone'));

        $place = filled($branch?->address) ? $branch->address : (string) $branch?->name;

        if ($notifiable->branch_id !== null && (int) $notifiable->branch_id !== (int) $event->branch_id) {
            $place .= ' ('.$branch?->name.' branch)';
        }

        return [
            'role' => $assignment->dutyRole->name,
            'event' => $event->name,
            'when' => $start?->format('l, F j, Y \a\t g:i A') ?? 'a date to be confirmed',
            'starts_at' => $start?->toIso8601String() ?? '',
            'place' => $place,
        ];
    }
}
