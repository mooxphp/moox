<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats\Strategies;

use Moox\EBilling\Formats\Contracts\GeneratorStrategyInterface;
use Moox\Zugferd\Contracts\ZugferdInvoice;
use Moox\Zugferd\ZugferdConverter;

final class ZugferdGeneratorStrategy implements GeneratorStrategyInterface
{
    public function __construct(
        private ZugferdConverter $converter,
    ) {}

    public function generateXml(ZugferdInvoice $invoice): string
    {
        return $this->converter->convert($invoice);
    }
}
