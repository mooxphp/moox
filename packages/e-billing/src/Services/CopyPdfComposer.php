<?php

declare(strict_types=1);

namespace Moox\EBilling\Services;

use Moox\EBilling\Support\QpdfBinary;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\PdfParserException;
use Symfony\Component\Process\Process;

/**
 * Stamps the XRechnung copy marking onto a visible PDF. No XML, no PDF/A.
 */
final class CopyPdfComposer
{
    public function stamp(string $sourcePdfPath): string
    {
        $term = trim((string) config('e-billing.copy_pdf.term', ''));
        $notice = trim((string) config('e-billing.copy_pdf.notice', ''));

        if ($term === '' || $notice === '') {
            throw new RuntimeException(
                'XRechnung copy marking is required. Set e-billing.copy_pdf.term and e-billing.copy_pdf.notice.'
            );
        }

        if (! is_file($sourcePdfPath)) {
            throw new RuntimeException("Copy source PDF not found at [{$sourcePdfPath}].");
        }

        $workingPath = $this->prepareReadablePdf($sourcePdfPath);

        try {
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($workingPath);

            if ($pageCount < 1) {
                throw new RuntimeException('Copy source PDF has no pages.');
            }

            $termLatin1 = $this->toLatin1($term);
            $noticeLatin1 = $this->toLatin1($notice);

            for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
                $template = $pdf->importPage($pageNumber);
                $size = $pdf->getTemplateSize($template);
                $pageWidth = (float) $size['width'];
                $pageHeight = (float) $size['height'];
                $orientation = $pageWidth > $pageHeight ? 'L' : 'P';

                $pdf->AddPage($orientation, [$pageWidth, $pageHeight]);
                $pdf->useTemplate($template, 0, 0, $pageWidth, $pageHeight);

                $pdf->SetFont('Helvetica', 'B', 14);
                $pdf->SetTextColor(176, 0, 0);
                $pdf->SetXY(10, 8);
                $pdf->Cell($pageWidth - 20, 8, $termLatin1, 0, 0, 'R');

                $pdf->SetFont('Helvetica', '', 7);
                $pdf->SetTextColor(80, 80, 80);
                $pdf->SetXY(10, $pageHeight - 12);
                $pdf->MultiCell($pageWidth - 20, 3.5, $noticeLatin1, 0, 'C');
            }

            /** @var string $binary */
            $binary = $pdf->Output('S');

            return $binary;
        } finally {
            if ($workingPath !== $sourcePdfPath && is_file($workingPath)) {
                @unlink($workingPath);
            }
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
        $tempPath = tempnam(sys_get_temp_dir(), 'ebilling-copy-');
        if ($tempPath === false) {
            throw new RuntimeException('Could not allocate temporary file for copy PDF.');
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
                'Could not rewrite PDF for XRechnung copy: '.$process->getErrorOutput()
            );
        }

        return $tempPath;
    }

    private function toLatin1(string $value): string
    {
        $converted = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $value);

        return is_string($converted) ? $converted : $value;
    }
}
