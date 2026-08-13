<?php

declare(strict_types=1);

namespace Moox\PdfParser;

use Moox\PdfParser\Data\ParsedDocument;
use Spatie\PdfToText\Pdf;

class PdfParser
{
    public function __construct(
        private ?string $pdftotextPath = null,
    ) {
    }

    /**
     * Use the configured binary when it exists; otherwise Spatie auto-discovers pdftotext.
     */
    public static function usableBinaryPath(mixed $configured): ?string
    {
        if (! is_string($configured) || $configured === '') {
            return null;
        }

        return is_executable($configured) ? $configured : null;
    }

    /**
     * Extract text from a PDF file.
     */
    public function parse(string $pdfPath): ParsedDocument
    {
        if (! file_exists($pdfPath)) {
            throw new \InvalidArgumentException("PDF file not found: {$pdfPath}");
        }

        $pdf = $this->pdftotextPath
            ? new Pdf($this->pdftotextPath)
            : new Pdf;

        $text = $pdf->setPdf($pdfPath)->text();

        return new ParsedDocument(
            filePath: $pdfPath,
            text: $text,
            parser: 'spatie/pdf-to-text',
        );
    }

    /**
     * Extract text with layout preservation (column structure).
     */
    public function parseWithLayout(string $pdfPath): ParsedDocument
    {
        if (! file_exists($pdfPath)) {
            throw new \InvalidArgumentException("PDF file not found: {$pdfPath}");
        }

        $pdf = $this->pdftotextPath
            ? new Pdf($this->pdftotextPath)
            : new Pdf;

        $text = $pdf
            ->setPdf($pdfPath)
            ->setOptions(['-layout'])
            ->text();

        return new ParsedDocument(
            filePath: $pdfPath,
            text: $text,
            parser: 'spatie/pdf-to-text',
            layout: true,
        );
    }
}
