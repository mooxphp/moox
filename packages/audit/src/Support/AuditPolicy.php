<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

/**
 * Effective per-model audit policy.
 *
 * @param  ?int  $retentionDays  Number of days to retain entries; null means never prune.
 */
final readonly class AuditPolicy
{
    /**
     * @param  class-string  $modelClass
     */
    public function __construct(
        public string $modelClass,
        public bool $audited,
        public bool $appendOnly,
        public ?int $retentionDays,
    ) {
    }

    public function neverPrune(): bool
    {
        return $this->appendOnly || $this->retentionDays === null;
    }
}
