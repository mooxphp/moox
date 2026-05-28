<?php

declare(strict_types=1);

namespace Moox\Security\Jobs\Passwords;

use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;
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
        $userModel = config('security.password_reset_links.model');

        if (! is_string($userModel) || ! class_exists($userModel)) {
            return;
        }

        $brokerName = config('security.password_reset_links.broker', config('auth.defaults.passwords', 'users'));
        $panelId = config('security.password_reset_links.panel', 'admin');
        /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
        $broker = Password::broker($brokerName);

        /** @var array<string, true> $processedEmails */
        $processedEmails = [];

        foreach ($userModel::query()->cursor() as $user) {
            if (! $user instanceof CanResetPassword) {
                continue;
            }

            if (! method_exists($user, 'notify')) {
                continue;
            }

            $email = $user->getEmailForPasswordReset();

            if ($email === '' || isset($processedEmails[$email])) {
                continue;
            }

            $processedEmails[$email] = true;

            $token = $broker->createToken($user);

            $user->notify(PasswordResetNotification::forToken($token, $panelId));
        }
    }
}
