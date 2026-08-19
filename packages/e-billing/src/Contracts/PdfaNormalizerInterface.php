<?php

declare(strict_types=1);

namespace Moox\EBilling\Contracts;

interface PdfaNormalizerInterface
{
    /**
     * Return an absolute filesystem path to a PDF/A-3-conformant container.
     * The default binding is a passthrough and does not claim conformance.
     */
    public function normalize(string $absolutePdfPath): string;
}
