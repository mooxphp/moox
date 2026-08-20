<?php

declare(strict_types=1);

namespace Moox\Msgraph\Exceptions;

use Throwable;

class GraphRateLimitException extends GraphException
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
