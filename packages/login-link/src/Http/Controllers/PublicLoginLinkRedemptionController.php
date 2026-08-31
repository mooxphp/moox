<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Services\LoginLinkRedemptionService;

class PublicLoginLinkRedemptionController extends Controller
{
    public function __invoke(Request $request, int|string $loginLink): RedirectResponse|Response
    {
        $result = app(LoginLinkRedemptionService::class)->redeem($loginLink, null);

        if (! $result) {
            return $this->unavailable($loginLink);
        }

        return $result;
    }

    public function unavailable(int|string $loginLinkId): Response
    {
        $view = config('login-link.public_unavailable_view', 'login-link::public.unavailable');

        if (! is_string($view) || $view === '') {
            $view = 'login-link::public.unavailable';
        }

        return response()->view($view, [
            'reason' => app(LoginLinkRedemptionService::class)->failureReason($loginLinkId),
            'supportName' => $this->supportValue('name'),
            'supportEmail' => $this->supportValue('email'),
            'supportPhone' => $this->supportValue('phone'),
        ]);
    }

    private function supportValue(string $key): ?string
    {
        $value = config('login-link.public_support.'.$key);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
