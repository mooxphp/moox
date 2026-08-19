<?php

declare(strict_types=1);

namespace Moox\LoginLink\Handlers;

/**
 * Example: confirm a recipient received a campaign mailing. Does not authenticate.
 * Pair with invalidate_prior=false so sibling recipients keep their own links.
 */
class MassMailRedemptionHandler extends PublicExampleRedemptionHandler
{
    public const SESSION_KEY = 'login_link.example.mass_mail';

    protected function sessionKey(): string
    {
        return self::SESSION_KEY;
    }

    protected function redirectRoute(): string
    {
        return 'login-link.examples.mailing-confirmed';
    }
}
