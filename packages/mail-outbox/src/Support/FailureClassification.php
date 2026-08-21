<?php

declare(strict_types=1);

namespace Moox\MailOutbox\Support;

final readonly class FailureClassification
{
    public function __construct(
        public FailureKind $kind,
        public ?int $retryAfterSeconds = null,
    ) {
    }

    public function isPermanent(): bool
    {
        return $this->kind === FailureKind::Permanent;
    }
}
