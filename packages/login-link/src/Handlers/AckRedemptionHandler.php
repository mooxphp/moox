<?php

declare(strict_types=1);

namespace Moox\LoginLink\Handlers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Events\ProcessLinkAcknowledged;
use Moox\LoginLink\Models\LoginLink;

/**
 * Built-in non-login handler: proves the signed-link engine without authenticating.
 * Loads the polymorphic subject, fires {@see ProcessLinkAcknowledged}, redirects.
 * Never calls Auth::login — real verification handlers live in consumer packages.
 */
class AckRedemptionHandler implements RedemptionHandler
{
    public function handle(LoginLink $loginLink, string $panelId): ?RedirectResponse
    {
        $subject = $loginLink->subject()->first();

        if (! $subject instanceof Model) {
            return null;
        }

        ProcessLinkAcknowledged::dispatch($loginLink, $subject, $panelId);

        $redirectUrl = config('login-link.ack.redirect_url', '/');

        if (! is_string($redirectUrl) || $redirectUrl === '') {
            $redirectUrl = '/';
        }

        return redirect()->to($redirectUrl);
    }
}
