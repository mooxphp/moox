<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Exceptions;

use Exception;

final class MessageTooLargeException extends Exception
{
    public function __construct(
        public readonly int $actualBytes,
        public readonly int $maxBytes,
    ) {
        parent::__construct(
            "Rendered message size {$actualBytes} bytes exceeds configured ceiling of {$maxBytes} bytes."
        );
    }
}
