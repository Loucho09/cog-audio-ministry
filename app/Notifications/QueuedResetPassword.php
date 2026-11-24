<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use SensitiveParameter;

/** The normal "forgot password" email, sent through the queue (encrypted: it carries a token). */
class QueuedResetPassword extends ResetPassword implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public function __construct(#[SensitiveParameter] $token)
    {
        parent::__construct($token);

        $this->afterCommit();
    }
}
