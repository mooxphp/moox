<?php

declare(strict_types=1);

namespace Moox\EBilling\Exceptions;

use RuntimeException;

final class UnresolvedCodelistLabelException extends RuntimeException
{
    public function __construct(
        public readonly string $codelist,
        public readonly string $input,
    ) {
        parent::__construct(
            "Could not resolve {$codelist} code for input [{$input}]."
        );
    }
}
