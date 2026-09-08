<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * Concrete format id + profile used for one generation attempt.
 */
final readonly class EffectiveFormat
{
    public function __construct(
        public string $format,
        public string $profile,
    ) {
    }
}
