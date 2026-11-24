<?php

namespace App\Notifications;

use App\Support\InviteToken;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeSetPassword extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => InviteToken::wrap($this->token),
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Welcome to COG Audio Ministry')
            ->greeting('Welcome to COG Audio Ministry!')
            ->line('We are happy to have you with us. Please set your password to start using your account.')
            ->action('Set your password', $url)
            ->line('This link lasts 3 days. If it expires, ask your branch head to send you a new one.')
            ->line('If you were not expecting this email, you can ignore it.');
    }
}
