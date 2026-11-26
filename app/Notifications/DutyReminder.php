<?php

namespace App\Notifications;

use App\Enums\DutyStatus;
use App\Models\DutyAssignment;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Reminds a member of an upcoming duty ("day_before" or "soon"). Always in-app; email only if they have not turned it off. */
class DutyReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public const DAY_BEFORE = 'day_before';

    public const SOON = 'soon';

    public $deleteWhenMissingModels = true;

    /** Event start the reminder was claimed for; if it moves before sending, the reminder is dropped. */
    public ?CarbonInterface $expectedStart;

    public function __construct(public DutyAssignment $assignment, public string $kind, ?CarbonInterface $expectedStart = null)
    {
        $this->expectedStart = $expectedStart ?? $assignment->loadMissing('event')->event?->starts_at;
        $this->afterCommit();
    }

    /** Last check at send time: the duty must still be accepted, the member's own, and the start unchanged. */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        $assignment = $this->assignment->fresh(['event']);
        $start = $assignment?->event?->starts_at;

        return $assignment !== null
            && $assignment->status === DutyStatus::Accepted
            && $assignment->user_id !== null
            && (int) $assignment->user_id === (int) $notifiable->id
            && $start !== null
            && $start->isFuture()
            && $this->expectedStart !== null
            && $start->equalTo($this->expectedStart);
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ($notifiable->email_notifications ?? true) ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->details();

        return (new MailMessage)
            ->subject('Reminder: '.$d['role'].' '.$d['phrase'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line("Reminder: you're serving {$d['phrase']}. Thank you for serving.")
            ->line('Duty: '.$d['role'])
            ->line('Event: '.$d['event'])
            ->line('When: '.$d['when'])
            ->line('Where: '.$d['place'])
            ->action('Open my dashboard', route('dashboard'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $d = $this->details();

        return [
            'type' => $this->kind === self::SOON ? 'duty_reminder_soon' : 'duty_reminder_day',
            'assignment_id' => $this->assignment->id,
            'event_id' => $this->assignment->event_id,
            'role' => $d['role'],
            'event_title' => $d['event'],
            'starts_at' => $d['starts_at'],
            'place' => $d['place'],
            'message' => "Reminder: you're serving {$d['phrase']} as {$d['role']} at {$d['event']}, {$d['when']}, {$d['place']}.",
        ];
    }

    /** @return array<string, string> */
    private function details(): array
    {
        $assignment = $this->assignment->loadMissing('event.branch', 'dutyRole');
        $event = $assignment->event;
        $branch = $event?->branch;
        $start = $event?->starts_at?->copy()->timezone(config('app.timezone'));

        $hours = (int) config('ministry.reminders.soon_hours', 2);
        $phrase = $this->kind === self::SOON
            ? 'in about '.$hours.' '.($hours === 1 ? 'hour' : 'hours')
            : 'tomorrow';

        return [
            'role' => (string) $assignment->dutyRole?->name,
            'event' => (string) $event?->name,
            'when' => $start?->format('l, F j \a\t g:i A') ?? 'a date to be confirmed',
            'starts_at' => $start?->toIso8601String() ?? '',
            'place' => filled($branch?->address) ? $branch->address : (string) $branch?->name,
            'phrase' => $phrase,
        ];
    }
}
