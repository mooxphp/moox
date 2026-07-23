<?php

declare(strict_types=1);

namespace Moox\EBilling\Jobs;

/**
 * Absolute paths for artifact validation; tempXmlPath is cleaned up by the job.
 *
 * @internal
 */
final readonly class ValidationInputs
{
    public function __construct(
        public string $absoluteXmlPath,
        public ?string $absolutePdfPath,
        public ?string $tempXmlPath,
    ) {
    }
}
