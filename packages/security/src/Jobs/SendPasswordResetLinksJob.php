<?php

namespace Moox\Security\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Jobs\Traits\JobProgress;

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
        $usermodel = config('auth.providers.users.model');
        $users = $usermodel::all();

        foreach ($users as $user) {
            $token = app('auth.password.broker')->createToken($user);
            $notification = new \Filament\Notifications\Auth\ResetPassword($token);
            $notification->url = \Filament\Facades\Filament::getResetPasswordUrl($token, $user);

            $user->notify($notification);

        }
    }
}
