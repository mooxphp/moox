<?php

declare(strict_types=1);

namespace Moox\LoginLink\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Moox\LoginLink\Contracts\RendersUnavailablePage;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Services\LoginLinkRedemptionService;

class PublicLoginLinkRedemptionController extends Controller
{
    public const DEMO_VIEW = 'login-link::public.unavailable';

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
        $reason = app(LoginLinkRedemptionService::class)->failureReason($loginLinkId);
        $handler = app(LoginLinkRedemptionService::class)->handlerFor($loginLinkId);

        if ($handler instanceof RendersUnavailablePage) {
            $loginLink = LoginLink::query()->find($loginLinkId);

            if ($loginLink instanceof LoginLink) {
                $custom = $handler->unavailable($loginLink, $reason);

                if ($custom instanceof Response) {
                    return $custom;
                }
            }
        }

        return response()->view(self::DEMO_VIEW, [
            'reason' => $reason,
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
