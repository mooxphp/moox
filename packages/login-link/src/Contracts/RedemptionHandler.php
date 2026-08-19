<?php

declare(strict_types=1);

namespace Moox\LoginLink\Contracts;

use Illuminate\Http\RedirectResponse;
use Moox\LoginLink\Models\LoginLink;

interface RedemptionHandler
{
    /**
     * Redeem a validated, unused login link.
     *
     * $panelId is set for auth-context links; null for public-context links.
     * Return a redirect on success, or null when redemption must fail closed.
     */
    public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse;
}
