<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats\Contracts;

use Moox\Zugferd\Contracts\ZugferdInvoice;

interface GeneratorStrategyInterface
{
    public function generateXml(ZugferdInvoice $invoice, string $profile): string;
}
