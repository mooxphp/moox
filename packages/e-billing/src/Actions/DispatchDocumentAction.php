<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use Moox\EBilling\Approval\DocumentDispatchGuard;
use Moox\EBilling\Models\EbillingDocument;

final class DispatchDocumentAction
{
    public function __construct(
        private readonly DocumentDispatchGuard $dispatchGuard,
    ) {
    }

    /**
     * Dispatch seam for delivery (#19). Refuses unapproved or undeliverable documents.
     */
    public function execute(EbillingDocument $document): void
    {
        $this->dispatchGuard->assertDispatchable($document);
    }
}
