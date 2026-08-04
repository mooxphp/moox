<?php

declare(strict_types=1);

namespace Moox\LoginLink\Contracts;

use Illuminate\Http\RedirectResponse;
use Moox\LoginLink\Models\LoginLink;

interface RedemptionHandler
{
    /**
     * Redeem a validated, unused login link for the given panel.
     *
     * Return a redirect on success, or null when redemption must fail closed
     * (missing subject, wrong model, panel access denied, etc.).
     */
    public function handle(LoginLink $loginLink, string $panelId): ?RedirectResponse;
}
