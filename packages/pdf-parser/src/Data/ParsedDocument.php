<?php

declare(strict_types=1);

namespace Moox\PdfParser\Data;

class ParsedDocument
{
    public function __construct(
        public readonly string $filePath,
        public readonly string $text,
        public readonly string $parser,
        public readonly bool $layout = false,
    ) {
    }

    public function isEmpty(): bool
    {
        return trim($this->text) === '';
    }

    public function lines(): array
    {
        return explode("\n", $this->text);
    }
}
