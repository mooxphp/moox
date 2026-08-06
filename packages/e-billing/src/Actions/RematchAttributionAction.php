<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;

/**
 * Explicit re-match: reset to pre-confirmation state and re-run validation.
 * Manual attributions are left untouched by {@see InvoiceFieldValidator}.
 */
final class RematchAttributionAction
{
    public function __construct(
        private InvoiceFieldValidator $validator,
    ) {}

    public function execute(EbillingDocument $document): void
    {
        // Sanctioned backward move: automatic path must not re-enter confirmed/validated.
        $document->review_status = InvoiceProcessingStatus::ParserCreated;
        $document->save();

        $this->validator->validate($document);
    }
}
