<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

final class BulkItemResult
{
    public function __construct(
        public readonly string $status,
        public readonly ?string $errorMessage = null,
        public readonly ?string $destinationKey = null,
        public readonly ?string $sourceLabel = null,
        public readonly ?string $sourceReference = null,
    ) {
    }
}
