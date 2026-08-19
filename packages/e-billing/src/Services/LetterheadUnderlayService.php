<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Moox\EBilling\Support\QpdfBinary;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use Symfony\Component\Process\Process;

/**
 * Composes letterhead + comWORK content using FPDI (transitive via moox/zugferd → horstoeko/zugferd).
 * qpdf is only used to rewrite encrypted or FPDI-unreadable inputs.
 */
class LetterheadUnderlayService
{
    /**
     * @param  float  $offsetXMm  Shift letterhead right (+) / left (-) in millimetres.
     * @param  float  $offsetYMm  Shift letterhead down (+) / up (-) in millimetres.
     */
    public function compose(
        string $sourcePdfPath,
        string $letterheadPdfPath,
        float $offsetXMm = 0.0,
        float $offsetYMm = 0.0,
    ): string {
        if (! is_file($sourcePdfPath)) {
            throw new RuntimeException("Source PDF not found at [{$sourcePdfPath}].");
        }

        if (! is_file($letterheadPdfPath)) {
            throw new RuntimeException("Letterhead PDF not found at [{$letterheadPdfPath}].");
        }

        $workingSourcePath = $this->prepareReadablePdf($sourcePdfPath);
        $workingLetterheadPath = $this->prepareReadablePdf($letterheadPdfPath);

        try {
            $pdf = new Fpdi;

            $sourcePageCount = $pdf->setSourceFile($workingSourcePath);
            if ($sourcePageCount < 1) {
                throw new RuntimeException('Source PDF has no pages.');
            }

            $letterheadPageCount = $pdf->setSourceFile($workingLetterheadPath);
            if ($letterheadPageCount < 1) {
                throw new RuntimeException('Letterhead PDF has no pages.');
            }

            for ($pageNumber = 1; $pageNumber <= $sourcePageCount; $pageNumber++) {
                $pdf->setSourceFile($workingLetterheadPath);
                // Print letterheads are often PDF/X with bleed. TrimBox is the
                // pin-registered A4 area that matches comWORK's content PDF.
                $letterheadTemplate = $pdf->importPage(
                    min($pageNumber, $letterheadPageCount),
                    'TrimBox',
                );
                $letterheadSize = $pdf->getTemplateSize($letterheadTemplate);

                $pdf->setSourceFile($workingSourcePath);
                $sourceTemplate = $pdf->importPage($pageNumber);
                $sourceSize = $pdf->getTemplateSize($sourceTemplate);

                $pageWidth = (float) $sourceSize['width'];
                $pageHeight = (float) $sourceSize['height'];
                $orientation = $pageWidth > $pageHeight ? 'L' : 'P';

                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);

                $pdf->useTemplate(
                    $letterheadTemplate,
                    $offsetXMm,
                    $offsetYMm,
                    (float) $letterheadSize['width'],
                    (float) $letterheadSize['height'],
                );
                $pdf->useTemplate($sourceTemplate, 0, 0, $pageWidth, $pageHeight);
            }

            /** @var string $binary */
            $binary = $pdf->Output('S');

            return $binary;
        } finally {
            $this->deleteTemporaryPdf($workingSourcePath, $sourcePdfPath);
            $this->deleteTemporaryPdf($workingLetterheadPath, $letterheadPdfPath);
        }
    }

    private function prepareReadablePdf(string $pdfPath): string
    {
        try {
            $probe = new Fpdi;
            $probe->setSourceFile($pdfPath);

            return $pdfPath;
        } catch (PdfParserException) {
            return $this->rewritePdfForFpdi($pdfPath);
        }
    }

    private function rewritePdfForFpdi(string $sourcePdfPath): string
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'ebilling-fpdi-');
        if ($tempPath === false) {
            throw new RuntimeException('Could not allocate temporary file for FPDI-readable PDF.');
        }

        $process = new Process([
            QpdfBinary::resolve(),
            '--decrypt',
            '--object-streams=disable',
            $sourcePdfPath,
            $tempPath,
        ]);
        $process->setTimeout(30);
        $process->run();

        $exitCode = $process->getExitCode();
        $rewritten = is_file($tempPath) && filesize($tempPath) > 8;

        if (! $rewritten || ($exitCode !== 0 && $exitCode !== 3)) {
            @unlink($tempPath);

            throw new RuntimeException(
                'Could not rewrite PDF for letterhead overlay: '.$process->getErrorOutput()
            );
        }

        return $tempPath;
    }

    private function deleteTemporaryPdf(string $path, string $originalPath): void
    {
        if ($path !== $originalPath && is_file($path)) {
            @unlink($path);
        }
    }
}
