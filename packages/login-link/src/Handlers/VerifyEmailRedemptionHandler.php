<?php

declare(strict_types=1);

namespace Moox\LoginLink\Handlers;

/**
 * Example: confirm that this mailbox belongs to the subject. Does not authenticate.
 */
class VerifyEmailRedemptionHandler extends PublicExampleRedemptionHandler
{
    public const SESSION_KEY = 'login_link.example.verify_email';

    protected function sessionKey(): string
    {
        return self::SESSION_KEY;
    }

    protected function redirectRoute(): string
    {
        return 'login-link.examples.email-verified';
    }
}
