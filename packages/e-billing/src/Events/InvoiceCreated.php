<?php

declare(strict_types=1);

namespace Moox\EBilling\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Moox\Invoice\Models\Invoice;

final class InvoiceCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Invoice $invoice, // Extend Invoice in your host app if needed
    ) {}
}
