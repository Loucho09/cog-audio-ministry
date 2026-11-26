<?php

namespace App\Notifications;

use App\Filament\Resources\DutySwaps\DutySwapResource;
use App\Models\DutySwap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One class for the three swap events (claimed, approved, rejected); the text adapts to the
 * recipient (admin, offerer or claimer). Always in-app; email only if they have not turned it off.
 */
class DutySwapUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public const CLAIMED = 'swap_claimed';

    public const APPROVED = 'swap_approved';

    public const REJECTED = 'swap_rejected';

    public $deleteWhenMissingModels = true;

    public function __construct(public DutySwap $swap, public string $type)
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
            ->subject($d['subject'])
            ->greeting('Hello '.$notifiable->name.',')
            ->line($d['message'])
            ->line('Duty: '.$d['role'])
            ->line('Event: '.$d['event'])
            ->line('When: '.$d['when'])
            ->line('Status: '.$d['status'])
            ->action($d['action'], $d['url']);
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $d = $this->details($notifiable);

        return [
            'type' => $this->type,
            'swap_id' => $this->swap->id,
            'assignment_id' => $this->swap->duty_assignment_id,
            'event_id' => $this->swap->assignment->event_id,
            'role' => $d['role'],
            'event_title' => $d['event'],
            'starts_at' => $d['starts_at'],
            'status' => $d['status'],
            'url' => $d['url'],
            'message' => $d['message'],
        ];
    }

    /** @return array<string, string> */
    private function details(object $notifiable): array
    {
        $swap = $this->swap->loadMissing('assignment.event', 'assignment.dutyRole', 'offeredBy', 'claimedBy');
        $event = $swap->assignment->event;
        $start = $event->starts_at?->copy()->timezone(config('app.timezone'));

        $role = $swap->assignment->dutyRole->name;
        $title = $event->name;
        $when = $start?->format('l, F j, Y \a\t g:i A') ?? 'a date to be confirmed';
        $offerer = $swap->offeredBy?->name ?? 'A member';
        $claimer = $swap->claimedBy?->name ?? 'a member';
        $isAdmin = method_exists($notifiable, 'isAdminRole') && $notifiable->isAdminRole();
        $isOfferer = (int) $notifiable->getKey() === (int) $swap->offered_by;
        $what = "{$role} for {$title} on {$when}";

        [$subject, $message, $status] = match ($this->type) {
            self::CLAIMED => $isAdmin
                ? ['Swap needs approval: '.$role, "{$claimer} wants to take {$offerer}'s {$what}. Please approve or reject.", 'Waiting for admin approval']
                : ['Swap claimed: '.$role, "{$claimer} offered to take your {$what}. Waiting for an admin to approve.", 'Waiting for admin approval'],
            self::APPROVED => $isOfferer
                ? ['Swap approved: '.$role, "Your swap was approved. {$claimer} now covers {$what}. You are released from this duty.", 'Approved']
                : ['Swap approved: '.$role, "Your swap was approved. You now have {$what}.", 'Approved'],
            default => $isOfferer
                ? ['Swap not approved: '.$role, "Your swap was not approved. You are still on duty for {$what}.", 'Rejected']
                : ['Swap not approved: '.$role, "The swap for {$what} was not approved. {$offerer} stays on this duty.", 'Rejected'],
        };

        return [
            'subject' => $subject,
            'message' => $message,
            'status' => $status,
            'role' => $role,
            'event' => $title,
            'when' => $when,
            'starts_at' => $start?->toIso8601String() ?? '',
            'url' => $isAdmin ? DutySwapResource::getUrl('index', panel: 'admin') : route('dashboard'),
            'action' => $isAdmin ? 'Open swaps' : 'Open my dashboard',
        ];
    }
}
