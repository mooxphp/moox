<?php

declare(strict_types=1);

namespace Moox\LoginLink\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Moox\LoginLink\Models\LoginLink;

/**
 * Fired by the built-in non-login `ack` handler after a successful redemption.
 * Consumer packages can listen; concrete verification logic stays outside this package.
 */
class ProcessLinkAcknowledged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public LoginLink $loginLink,
        public Model $subject,
        public ?string $panelId,
    ) {
    }
}
