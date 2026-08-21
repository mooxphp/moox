<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Exceptions;

use Exception;
use Throwable;

/**
 * Marker for transient send failures that should be retried with backoff.
 */
final class TransientMailFailureException extends Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly ?int $retryAfterSeconds = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
