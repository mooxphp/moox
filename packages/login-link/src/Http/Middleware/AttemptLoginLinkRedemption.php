<?php

namespace Moox\LoginLink\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Moox\LoginLink\Services\LoginLinkRedemptionService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Backward compatibility: redeem magic links that still point at the login page with ?loginLink=.
 */
class AttemptLoginLinkRedemption
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('login-link.passwordless.enabled', false)) {
            return $next($request);
        }

        $patterns = config('login-link.login_route_patterns', ['filament.*.auth.login']);

        if (! $request->has('loginLink') || ! $request->routeIs(...(is_array($patterns) ? $patterns : [$patterns]))) {
            return $next($request);
        }

        if (! URL::hasValidSignature($request)) {
            return $next($request);
        }

        $panel = Filament::getCurrentPanel();
        $user = app(LoginLinkRedemptionService::class)->redeem(
            $request->query('loginLink'),
            (string) $panel->getId(),
        );

        if (! $user) {
            return $next($request);
        }

        Filament::auth()->login($user);
        session()->regenerate();
        session()->save();

        return redirect()->intended($panel->getUrl());
    }
}
