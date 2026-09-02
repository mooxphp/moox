<?php

declare(strict_types=1);

namespace Moox\EBilling\Approval;

use Moox\EBilling\Enums\AutoApproveFailureReason;

final class AutoApproveResult
{
    /**
     * @param  list<AutoApproveFailureReason>  $failures
     */
    public function __construct(
        private readonly array $failures,
    ) {
    }

    public function passed(): bool
    {
        return $this->failures === [];
    }

    /**
     * @return list<AutoApproveFailureReason>
     */
    public function failures(): array
    {
        return $this->failures;
    }

    public function hasFailure(AutoApproveFailureReason $reason): bool
    {
        return in_array($reason, $this->failures, true);
    }
}
