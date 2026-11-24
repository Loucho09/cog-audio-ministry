<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

/** The normal "forgot password" email, sent through the queue. */
class QueuedResetPassword extends ResetPassword implements ShouldQueue
{
    use Queueable;
}
