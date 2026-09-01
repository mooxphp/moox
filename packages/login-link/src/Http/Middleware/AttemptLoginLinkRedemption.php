<?php

namespace Moox\LoginLink\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Http\Controllers\PublicLoginLinkRedemptionController;
use Moox\LoginLink\Services\LoginLinkRedemptionService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backward compatibility: redeem magic links that still point at the login page with ?loginLink=.
 */
class AttemptLoginLinkRedemption
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('login-link.passwordless.enabled', true)) {
            return $next($request);
        }

        $patterns = config('login-link.login_route_patterns', ['filament.*.auth.login']);

        if (! $request->has('loginLink') || ! $request->routeIs(...(is_array($patterns) ? $patterns : [$patterns]))) {
            return $next($request);
        }

        $loginLinkId = $request->query('loginLink');

        if (! is_int($loginLinkId) && ! is_string($loginLinkId)) {
            $loginLinkId = '';
        }

        if (! URL::hasValidSignature($request)) {
            return app(PublicLoginLinkRedemptionController::class)->unavailable($loginLinkId);
        }

        $panel = Filament::getCurrentPanel();
        $result = app(LoginLinkRedemptionService::class)->redeem(
            $loginLinkId,
            (string) $panel->getId(),
        );

        if (! $result) {
            return app(PublicLoginLinkRedemptionController::class)->unavailable($loginLinkId);
        }

        return $result;
    }
}
