<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

/**
 * Recipient preference for generation: a FormatRegistry format id plus an optional
 * hybrid profile override. For xrechnung, profile must be null (orchestrator throws otherwise).
 */
final readonly class FormatPreference
{
    public function __construct(
        public string $format,
        public ?string $profile = null,
    ) {
    }
}
