<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

use Moox\EBilling\Models\EbillingDocument;

interface SourcePdfPreparerInterface
{
    /**
     * Return an absolute filesystem path to the PDF that should become the
     * visible hybrid container (after optional letterhead overlay).
     */
    public function prepare(EbillingDocument $document): string;
}
