<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

final class ArtifactValidationFailed
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public string $ebillingDocumentId,
        public array $errors,
        public string $format,
    ) {
    }
}
