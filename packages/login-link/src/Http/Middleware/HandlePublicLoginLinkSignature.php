<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Moox\LoginLink\Http\Controllers\PublicLoginLinkRedemptionController;
use Symfony\Component\HttpFoundation\Response;

class HandlePublicLoginLinkSignature
{
    /**
     * Valid signatures continue to redeem. Expired or tampered signed URLs
     * still render the unavailable page instead of Laravel's 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        $loginLinkId = $request->route('loginLink');

        if (! is_int($loginLinkId) && ! is_string($loginLinkId)) {
            $loginLinkId = '';
        }

        return app(PublicLoginLinkRedemptionController::class)
            ->unavailable($loginLinkId);
    }
}
