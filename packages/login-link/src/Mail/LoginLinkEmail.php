<?php

declare(strict_types=1);

namespace Moox\LoginLink\Mail;

use Moox\LoginLink\Models\LoginLink;

/**
 * @deprecated Use ProcessLinkMail. Kept for backwards compatibility.
 *
 * MJML / mail-template rendering lives on ProcessLinkMail. This subclass only
 * preserves the original one-argument constructor.
 */
class LoginLinkEmail extends ProcessLinkMail
{
    public function __construct(LoginLink $loginLink)
    {
        parent::__construct($loginLink);
    }
}
