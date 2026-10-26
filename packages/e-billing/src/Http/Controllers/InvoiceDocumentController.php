<?php

declare(strict_types=1);

namespace Moox\EBilling\Http\Controllers;

use Filament\Facades\Filament;
use Filament\Resources\Resource as FilamentResource;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Resources\CreditNoteResource;
use Moox\EBilling\Resources\IgnoredDocumentResource;
use Moox\EBilling\Resources\InvoiceResource;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoiceDocumentController
{
    /**
     * E-billing resources that can show a document, checked in this order.
     *
     * @var list<class-string<FilamentResource>>
     */
    private const DOCUMENT_RESOURCES = [
        InvoiceResource::class,
        CreditNoteResource::class,
        IgnoredDocumentResource::class,
    ];

    /**
     * Stream the preview PDF: hybrid when present, otherwise the stored
     * XRechnung copy (watermarked), otherwise the source. Generated PDFs are only
     * shown while the document is deliverable; otherwise they may be stale.
     */
    public function previewOriginal(EbillingDocument $document): Response
    {
        $this->authorizeDocument($document);

        $generated = $document->isDeliverable() ? $this->previewStoredPdf($document) : null;
        if ($generated !== null) {
            return $this->streamPdf($generated['contents'], $generated['filename']);
        }

        $path = $document->sourceStoragePath();
        $this->guardPath($path);

        $contents = $document->sourcePreviewContents();
        abort_unless(is_string($contents), 404);

        $filename = $document->sourceOriginalFilename();

        return $this->streamPdf($contents, $filename);
    }

    /**
     * Download the ZUGFeRD PDF.
     */
    public function downloadZugferd(EbillingDocument $document): StreamedResponse
    {
        $this->authorizeDocument($document);
        $this->guardDeliverableArtifact($document);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $path = $document->pdf_storage_path;

        abort_unless(is_string($path) && $path !== '', 404);
        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamedDownloadFromDisk(
            $disk,
            $path,
            $document->downloadFilenameForStoredPath($path),
        );
    }

    /**
     * Download the raw XML.
     */
    public function downloadXml(EbillingDocument $document): StreamedResponse
    {
        $this->authorizeDocument($document);
        $this->guardDeliverableArtifact($document);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $path = $document->xml_storage_path;

        abort_unless(is_string($path) && $path !== '', 404);
        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamedDownloadFromDisk(
            $disk,
            $path,
            $document->downloadFilenameForStoredPath($path),
        );
    }

    /**
     * Download the stored XRechnung copy PDF (watermark baked in at generation).
     */
    public function downloadCopy(EbillingDocument $document): StreamedResponse
    {
        $this->authorizeDocument($document);
        $this->guardDeliverableArtifact($document);

        $disk = $document->storage_disk
            ?? (string) config('e-billing.zugferd.storage_disk', 'zugferd');
        $path = $document->copy_pdf_storage_path;

        abort_unless(is_string($path) && $path !== '', 404);
        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamedDownloadFromDisk(
            $disk,
            $path,
            $document->downloadFilenameForStoredPath($path),
        );
    }

    /**
     * Stored visual PDF for admin preview: hybrid first, then XRechnung copy.
     *
     * @return array{contents: string, filename: string}|null
     */
    private function previewStoredPdf(EbillingDocument $document): ?array
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
            'filename' => $document->downloadFilenameForStoredPath($path),
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

    /**
     * Same check as a Filament view page: one of the current panel's e-billing resources
     * must find the record in its own query and pass canView(). 404 otherwise, so a
     * guessed id does not reveal that the document exists.
     */
    private function authorizeDocument(EbillingDocument $document): void
    {
        $panelResources = Filament::getCurrentOrDefaultPanel()->getResources();

        foreach (self::DOCUMENT_RESOURCES as $resource) {
            if (! in_array($resource, $panelResources, true)) {
                continue;
            }

            $key = is_a($resource::getModel(), EbillingDocument::class, true)
                ? $document->getKey()
                : $document->invoice_id;

            if ($key === null) {
                continue;
            }

            $record = $resource::resolveRecordRouteBinding($key);

            if ($record !== null && $resource::canView($record)) {
                return;
            }
        }

        abort(404);
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
