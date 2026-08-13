<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\SourcePdfPreparerInterface;
use Moox\EBilling\Models\EbillingDocument;

final class PassthroughSourcePdfPreparer implements SourcePdfPreparerInterface
{
    public function prepare(EbillingDocument $document): string
    {
        return $document->sourceFullPath();
    }
}
