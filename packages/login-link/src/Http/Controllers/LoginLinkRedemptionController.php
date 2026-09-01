<?php

namespace Moox\LoginLink\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Services\LoginLinkRedemptionService;

class LoginLinkRedemptionController extends Controller
{
    public function __invoke(Request $request, int|string $loginLink): RedirectResponse|Response
    {
        $panel = Filament::getCurrentPanel();
        $panelId = (string) $panel->getId();

        $result = app(LoginLinkRedemptionService::class)->redeem($loginLink, $panelId);

        if (! $result) {
            return app(PublicLoginLinkRedemptionController::class)->unavailable($loginLink);
        }

        return $result;
    }
}
