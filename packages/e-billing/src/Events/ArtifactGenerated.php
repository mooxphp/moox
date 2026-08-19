<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

final class ArtifactGenerated
{
    public function __construct(
        public string $ebillingDocumentId,
        public string $format,
    ) {
    }
}
