<?php

declare(strict_types=1);

namespace Moox\LoginLink\Contracts;

use Illuminate\Http\Response;
use Moox\LoginLink\Models\LoginLink;

/**
 * Optional: process-specific used/expired/invalid page. Handlers that omit this
 * keep the packaged HTML demo (no host theme).
 */
interface RendersUnavailablePage
{
    public function unavailable(LoginLink $loginLink, string $reason): ?Response;
}
