<?php

declare(strict_types=1);

namespace Moox\EBilling\Actions;

use InvalidArgumentException;
use Moox\EBilling\Models\EbillingDocument;

/**
 * A bulk upload: one single manual upload per stored file, under exactly the single-upload rules.
 * A file that fails is skipped and reported; the others are created.
 */
final class CreateManualUploadBatchAction
{
    public function __construct(private readonly CreateManualUploadDocumentAction $createDocument)
    {
    }

    /**
     * @param  list<string>  $storedPaths  the files the upload actually stored; only these are processed
     * @param  array<string, string>  $originalFilenames  stored path => client file name
     * @param  list<array{
     *     source_pdf_path: string,
     *     document_type?: string|int|null,
     *     recipient_email?: ?string
     * }>  $rows  per-file details
     * @param  array{
     *     source_pdf_disk: string,
     *     scope: string,
     *     resource: string,
     *     requires_letterhead_overlay: bool,
     *     uploader_user_id: ?string
     * }  $shared
     * @return array{created: list<EbillingDocument>, failed: list<array{filename: string, reason: string}>}
     */
    public function execute(array $storedPaths, array $originalFilenames, array $rows, array $shared): array
    {
        $maxFiles = max(1, (int) config('e-billing.manual_upload.bulk_max_files', 50));

        if (count($storedPaths) > $maxFiles) {
            throw new InvalidArgumentException("A bulk upload takes at most {$maxFiles} files.");
        }

        $rowsByPath = [];
        foreach ($rows as $row) {
            $rowsByPath[$row['source_pdf_path']] = $row;
        }

        $created = [];
        $failed = [];

        foreach ($storedPaths as $path) {
            $filename = $originalFilenames[$path] ?? basename($path);
            $row = $rowsByPath[$path] ?? null;

            if ($row === null) {
                $failed[] = ['filename' => $filename, 'reason' => 'No document details were submitted for this file.'];

                continue;
            }

            try {
                $created[] = $this->createDocument->execute([
                    ...$shared,
                    'source_pdf_path' => $path,
                    'original_filename' => $filename,
                    'document_type' => $row['document_type'] ?? null,
                    'recipient_email' => $row['recipient_email'] ?? null,
                ]);
            } catch (InvalidArgumentException $exception) {
                $failed[] = ['filename' => $filename, 'reason' => $exception->getMessage()];
            }
        }

        return ['created' => $created, 'failed' => $failed];
    }
}
