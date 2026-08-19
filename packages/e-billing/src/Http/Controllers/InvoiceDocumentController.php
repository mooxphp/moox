<?php

declare(strict_types=1);

namespace Moox\EBilling\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Models\EbillingDocument;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoiceDocumentController
{
    /**
     * Stream the visible PDF (generated hybrid when present, otherwise the source).
     */
    public function previewOriginal(EbillingDocument $document): Response
    {
        $generated = $this->generatedPdfPreview($document);
        if ($generated !== null) {
            return $this->streamPdf($generated['contents'], $generated['filename']);
        }

        $path = $document->sourceStoragePath();
        $this->guardPath($path);

        $contents = $document->sourcePreviewContents();
        abort_unless(is_string($contents), 404);

        $filename = is_string($path) && $path !== ''
            ? basename($path)
            : $document->sourceOriginalFilename();

        return $this->streamPdf($contents, $filename);
    }

    /**
     * Download the ZUGFeRD PDF.
     */
    public function downloadZugferd(EbillingDocument $document): StreamedResponse
    {
        $this->guardDeliverableArtifact($document);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $path = $document->pdf_storage_path;

        abort_unless(is_string($path) && $path !== '', 404);
        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamedDownloadFromDisk($disk, $path, basename($path));
    }

    /**
     * Download the raw XML.
     */
    public function downloadXml(EbillingDocument $document): StreamedResponse
    {
        $this->guardDeliverableArtifact($document);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $path = $document->xml_storage_path;

        abort_unless(is_string($path) && $path !== '', 404);
        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamedDownloadFromDisk($disk, $path, basename($path));
    }

    /**
     * Download the human-readable XRechnung copy PDF (never the hybrid invoice).
     */
    public function downloadCopy(EbillingDocument $document): StreamedResponse
    {
        $this->guardDeliverableArtifact($document);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $path = $document->copy_pdf_storage_path;

        abort_unless(is_string($path) && $path !== '', 404);
        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamedDownloadFromDisk($disk, $path, basename($path));
    }

    /**
     * @return array{contents: string, filename: string}|null
     */
    private function generatedPdfPreview(EbillingDocument $document): ?array
    {
        $path = $document->humanReadablePdfStoragePath();
        if (! is_string($path) || $path === '') {
            return null;
        }

        $this->guardPath($path);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'contents' => $contents,
            'filename' => basename($path),
        ];
    }

    private function streamedDownloadFromDisk(string $disk, string $path, string $filename): StreamedResponse
    {
        $filesystem = Storage::disk($disk);

        if (! $filesystem instanceof FilesystemAdapter) {
            throw new \RuntimeException('Disk ['.$disk.'] does not use a Laravel filesystem adapter.');
        }

        return $filesystem->download($path, $filename);
    }

    private function guardDeliverableArtifact(EbillingDocument $document): void
    {
        abort_unless($document->isDeliverable(), 404);
    }

    private function guardPath(?string $path): void
    {
        abort_if($path === null, 404);
        abort_if(str_contains($path, '..'), 400);
    }

    private function streamPdf(string $content, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->safeDownloadFilename($filename).'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }

    private function safeDownloadFilename(string $filename): string
    {
        $basename = basename(str_replace('\\', '/', $filename));

        return str_replace(['"', "\r", "\n"], '', $basename);
    }
}
