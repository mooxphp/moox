<?php

declare(strict_types=1);

namespace Moox\Pdf\Exceptions;

use RuntimeException;
use Throwable;

class CouldNotRenderPdf extends RuntimeException
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
