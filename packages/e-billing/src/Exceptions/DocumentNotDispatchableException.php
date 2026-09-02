<?php

declare(strict_types=1);

namespace Moox\EBilling\Exceptions;

use RuntimeException;

final class DocumentNotDispatchableException extends RuntimeException
{
    public function __construct(
        public readonly string $reasonCode,
    ) {
        parent::__construct("Document is not dispatchable: {$reasonCode}");
    }
}
