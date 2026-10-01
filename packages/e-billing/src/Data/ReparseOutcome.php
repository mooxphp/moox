<?php

declare(strict_types=1);

namespace Moox\EBilling\Data;

use Moox\EBilling\Actions\ReparseDocumentAction;

/**
 * Result of {@see ReparseDocumentAction}: either reparsed, or refused with a reason key.
 */
final readonly class ReparseOutcome
{
    private function __construct(
        public bool $reparsed,
        public ?string $refusal = null,
        public ?string $oldNetTotal = null,
        public ?string $newNetTotal = null,
        public ?string $oldLineTotal = null,
        public ?string $newLineTotal = null,
    ) {
    }

    /**
     * Amounts are EUR strings with two decimals; old values are null when the document had no Invoice yet.
     */
    public static function reparsed(
        ?string $oldNetTotal,
        string $newNetTotal,
        ?string $oldLineTotal,
        string $newLineTotal,
    ): self {
        return new self(true, null, $oldNetTotal, $newNetTotal, $oldLineTotal, $newLineTotal);
    }

    public static function refused(string $reason): self
    {
        return new self(false, $reason);
    }
}
