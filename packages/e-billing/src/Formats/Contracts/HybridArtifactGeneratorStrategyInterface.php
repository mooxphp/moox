<?php

declare(strict_types=1);

namespace Moox\EBilling\Formats\Contracts;

interface HybridArtifactGeneratorStrategyInterface extends GeneratorStrategyInterface
{
    public function mergeXmlIntoPdf(string $xml, string $sourcePdfPath): string;

    public function extractXmlForValidation(string $pdfPath): string;
}
