<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Contracts\PdfaNormalizerInterface;

final class PassthroughPdfaNormalizer implements PdfaNormalizerInterface
{
    public function normalize(string $absolutePdfPath): string
    {
        return $absolutePdfPath;
    }
}
