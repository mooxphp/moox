<?php

declare(strict_types=1);

namespace Moox\EBilling\Resources\CreditNoteResource\Pages;

use Moox\EBilling\Resources\CreditNoteResource;
use Moox\EBilling\Resources\InvoiceResource\Pages\ViewInvoice;

final class ViewCreditNote extends ViewInvoice
{
    protected static string $resource = CreditNoteResource::class;
}
