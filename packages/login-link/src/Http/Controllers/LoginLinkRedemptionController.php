<?php

namespace Moox\LoginLink\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Services\LoginLinkRedemptionService;

class LoginLinkRedemptionController extends Controller
{
    public function __invoke(Request $request, int|string $loginLink): RedirectResponse
    {
        $panel = Filament::getCurrentPanel();
        $panelId = (string) $panel->getId();

        $user = app(LoginLinkRedemptionService::class)->redeem($loginLink, $panelId);

        if (! $user) {
            return redirect()->to($panel->getLoginUrl())
                ->with('login_link_error', __('login-link::translations.login_invalid_link_title'));
        }

        $guard = Filament::auth();
        $guard->login($user);
        session()->regenerate();
        session()->save();

        return redirect()->intended($panel->getUrl());
    }
}
