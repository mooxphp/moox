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
    use Dispatchable;
    use InteractsWithQueue;
    use JobProgress;
    use Queueable;
    use SerializesModels;

    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var int
     */
    public $timeout = 300;

    /**
     * @var int
     */
    public $maxExceptions = 1;

    /**
     * @var int
     */
    public $backoff = 350;

    public function handle(): void
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
