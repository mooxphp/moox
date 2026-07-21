<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats\Strategies;

use Moox\EBilling\Formats\Contracts\HybridArtifactGeneratorStrategyInterface;
use Moox\Zugferd\Contracts\ZugferdInvoice;
use Moox\Zugferd\ZugferdConverter;

final class ZugferdGeneratorStrategy implements HybridArtifactGeneratorStrategyInterface
{
    public function __construct(
        private ZugferdConverter $converter,
    ) {}

    public function generateXml(ZugferdInvoice $invoice): string
    {
        return $this->converter->convert($invoice);
    }

    public function mergeXmlIntoPdf(string $xml, string $sourcePdfPath): string
    {
        return $this->converter->mergePdfWithXml($sourcePdfPath, $xml);
    }

    public function extractXmlForValidation(string $pdfPath): string
    {
        return $this->converter->extractXmlFromPdf($pdfPath);
    }
}
