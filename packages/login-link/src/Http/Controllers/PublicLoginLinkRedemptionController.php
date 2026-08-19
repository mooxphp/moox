<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Services\LoginLinkRedemptionService;

class PublicLoginLinkRedemptionController extends Controller
{
    public function __invoke(Request $request, int|string $loginLink): RedirectResponse
    {
        $result = app(LoginLinkRedemptionService::class)->redeem($loginLink, null);

        if (! $result) {
            $redirect = config('login-link.public_invalid_redirect', '/');

            if (! is_string($redirect) || $redirect === '') {
                $redirect = '/';
            }

            return redirect()->to($redirect)
                ->with('login_link_error', __('login-link::translations.login_invalid_link_title'));
        }

        return $result;
    }
}
