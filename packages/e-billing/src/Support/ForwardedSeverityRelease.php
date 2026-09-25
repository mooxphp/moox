<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

final readonly class ForwardedSeverityRelease
{
    public function __construct(
        public string $field,
        public ?string $lineId,
        public string $reason,
        public mixed $releasedById,
        public string $releasedAt,
    ) {
    }
}
