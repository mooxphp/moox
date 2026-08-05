<?php

namespace Moox\LoginLink\Mail;

use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;
use Moox\LoginLink\Services\RedemptionHandlerRegistry;

/**
 * @deprecated Use ProcessLinkMail. Kept for backwards compatibility.
 */
class LoginLinkEmail extends ProcessLinkMail
{
    public function __construct(LoginLink $loginLink)
    {
        $process = LoginLinkProcess::query()
            ->where('slug', $loginLink->process ?: RedemptionHandlerRegistry::DEFAULT_PROCESS)
            ->first();

        parent::__construct($loginLink, $process);
    }
}
