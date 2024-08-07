<?php

namespace Moox\Security\Jobs\Passwords;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Jobs\Traits\JobProgress;
use Moox\Security\Notifications\Passwords\PasswordResetNotification;

class SendPasswordResetLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, JobProgress, Queueable, SerializesModels;

    public $tries;

    public $timeout;

    public $maxExceptions;

    public $backoff;

    public function __construct()
    {
        $this->tries = 3;
        $this->timeout = 300;
        $this->maxExceptions = 1;
        $this->backoff = 350;
    }

    public function handle()
    {
        $usermodel = config('security.password_reset_links.model');
        $users = $usermodel::all();

        foreach ($users as $user) {
            $token = app('auth.password.broker')->createToken($user);
            $notification = new PasswordResetNotification($token);

            $user->notify($notification);
        }
    }
}
