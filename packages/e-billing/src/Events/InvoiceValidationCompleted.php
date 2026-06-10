<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Moox\EBilling\Models\EbillingDocument;

final class InvoiceValidationCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly EbillingDocument $document,
        public readonly bool $needsHumanReview,
    ) {}
}
