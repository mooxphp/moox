<?php

declare(strict_types=1);

namespace Moox\Pdf\Contracts;

interface PdfDocument
{
    public function draw(PdfCanvas $canvas): void;
}
