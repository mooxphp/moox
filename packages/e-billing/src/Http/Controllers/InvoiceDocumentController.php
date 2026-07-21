<?php

declare(strict_types=1);

namespace Moox\EBilling\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Moox\EBilling\Models\EbillingDocument;
use Moox\MailInbox\Models\InboxAttachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InvoiceDocumentController
{
    /**
     * Stream the original source PDF for inline viewing.
     */
    public function previewOriginal(InboxAttachment $attachment): Response
    {
        $this->guardAttachment($attachment);

        $disk = $attachment->storage_disk ?? (string) config('mail-inbox.attachments.disk', 'local');
        $path = $attachment->storage_path;

        $this->guardPath($path);
        abort_unless(Storage::disk($disk)->exists($path), 404);

        return $this->streamPdf(
            Storage::disk($disk)->get($path),
            $this->downloadFilename($attachment, 'storage_path', 'pdf'),
        );
    }

    /**
     * Download the ZUGFeRD PDF.
     */
    public function downloadZugferd(InboxAttachment $attachment): StreamedResponse
    {
        $document = $this->guardAttachmentWithDocument($attachment);

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
            $this->artifactDownloadFilename($attachment, $document->pdf_storage_path, 'pdf'),
        );
    }

    /**
     * Download the raw XML.
     */
    public function downloadXml(InboxAttachment $attachment): StreamedResponse
    {
        $document = $this->guardAttachmentWithDocument($attachment);

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
            $this->artifactDownloadFilename($attachment, $document->xml_storage_path, 'xml'),
        );
    }

    private function streamedDownloadFromDisk(string $disk, string $path, string $filename): StreamedResponse
    {
        $filesystem = Storage::disk($disk);

        if (! $filesystem instanceof FilesystemAdapter) {
            throw new \RuntimeException('Disk ['.$disk.'] does not use a Laravel filesystem adapter.');
        }

        return $filesystem->download($path, $filename);
    }

    private function guardAttachmentWithDocument(InboxAttachment $attachment): EbillingDocument
    {
        $document = EbillingDocument::forSourceAttachment($attachment);

        abort_if(
            $document === null
            || $document->invoice_id === null,
            404,
        );

        return $document;
    }

    private function guardAttachment(InboxAttachment $attachment): void
    {
        $this->guardAttachmentWithDocument($attachment);
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

    private function downloadFilename(InboxAttachment $attachment, string $storagePathColumn, string $extension): string
    {
        $path = $attachment->{$storagePathColumn};
        if ($path === null || ! is_string($path) || $path === '') {
            return pathinfo($attachment->filename ?? 'document', PATHINFO_FILENAME).'.'.$extension;
        }

        return basename($path);
    }

    private function artifactDownloadFilename(InboxAttachment $attachment, ?string $storagePath, string $extension): string
    {
        if ($storagePath === null || $storagePath === '') {
            return pathinfo($attachment->filename ?? 'document', PATHINFO_FILENAME).'.'.$extension;
        }

        return basename($storagePath);
    }

    private function streamPdf(string $content, string $filename): Response
    {
        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ]);
    }
}
