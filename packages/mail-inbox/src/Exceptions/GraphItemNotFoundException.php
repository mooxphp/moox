<?php

declare(strict_types=1);

namespace Moox\MailInbox\Exceptions;

use Throwable;

class GraphItemNotFoundException extends GraphException
{
    public function __construct(string $message = '', int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
