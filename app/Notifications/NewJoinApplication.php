<?php

namespace App\Notifications;

use App\Models\JoinApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewJoinApplication extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public string $branchName;

    public function __construct(public JoinApplication $application)
    {
        // Matters when dispatched inside a transaction (e.g. a later approve flow): send only after commit.
        $this->afterCommit();

        // Kept as a plain string so each queued job needs no extra branch query.
        $this->branchName = $application->loadMissing('branch')->branch->name;
    }

    /** Backslash-escape Markdown characters so user text cannot form links or formatting. */
    private static function escapeMarkdown(string $text): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|])/', '\\\\$1', $text);
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->application;
        $ministry = config('ministry.name', config('app.name'));
        $submitted = $application->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A');

        return (new MailMessage)
            ->subject('New join application for '.$this->branchName)
            ->markdown('mail.new-join-application', [
                'ministry' => $ministry,
                'name' => self::escapeMarkdown($application->full_name),
                'branch' => self::escapeMarkdown($this->branchName),
                'interests' => implode(', ', $application->interests ?? []),
                'submitted' => $submitted,
                // TODO: Slice 4 - link to the Filament JoinApplication resource view page.
                'url' => url('/admin'),
            ]);
    }
}
