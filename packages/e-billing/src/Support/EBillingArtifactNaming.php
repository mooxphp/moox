<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Moox\MailInbox\Models\InboxAttachment;
use RuntimeException;
use Throwable;

/**
 * Naming helper for artifacts the EBilling Gateway pipeline writes to the
 * `zugferd` disk (ZUGFeRD XML, merged PDF/A-3). KOSIT report basenames
 * derive from these via pathinfo() in KositService. Lives in the gateway
 * because the gateway owns the artifact lifecycle.
 *
 * Directory layout matches mail-inbox: `{scope}/{Y}/{m}/{d}/…`.
 * Basenames derive from the original PDF attachment filename (sanitized).
 */
final class EBillingArtifactNaming
{
    /**
     * Sanitized basename derived from the original PDF attachment filename.
     * Does NOT consider collisions — pure function of $attachment->filename.
     */
    public static function basenameFor(InboxAttachment $attachment): string
    {
        $candidate = pathinfo($attachment->filename ?? '', PATHINFO_FILENAME);

        $candidate = preg_replace('/[^\p{L}\p{N}._-]+/u', '_', $candidate);
        $candidate = is_string($candidate) ? $candidate : '';

        $candidate = preg_replace('/_+/', '_', $candidate);
        $candidate = is_string($candidate) ? $candidate : '';
        $candidate = trim($candidate, '. _');

        if ($candidate === '') {
            throw new RuntimeException(sprintf(
                'Cannot derive storage basename from attachment filename: %s',
                $attachment->filename ?? '(null)',
            ));
        }

        return $candidate;
    }

    /**
     * Returns a basename that does not collide with any existing .xml or .pdf
     * in {$disk}/{$directory}. Synchronized across both extensions: if either
     * .xml or .pdf exists for a candidate, the candidate is skipped and the
     * next numeric suffix is tried.
     *
     * Call this exactly once per attachment, in GenerateArtifactJob, when no
     * existing storage path is set on the document yet. Subsequent retries
     * reuse the stored xml_storage_path and pdf_storage_path.
     */
    public static function uniqueBasenameFor(
        InboxAttachment $attachment,
        string $disk,
        string $directory,
    ): string {
        $base = self::basenameFor($attachment);
        $candidate = $base;
        $counter = 2;
        $storage = Storage::disk($disk);

        while (
            $storage->exists($directory.'/'.$candidate.'.xml')
            || $storage->exists($directory.'/'.$candidate.'.pdf')
        ) {
            $candidate = $base.'_'.$counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Invoice-date directory segment `{Y}/{m}/{d}` (e.g. `2026/03/15`).
     * Uses invoice date when provided; falls back to `now()` when missing or unparseable.
     */
    public static function invoiceDatePathSegment(DateTimeInterface|string|null $invoiceDate = null): string
    {
        if ($invoiceDate === null) {
            return now()->format('Y/m/d');
        }

        if (is_string($invoiceDate) && trim($invoiceDate) === '') {
            return now()->format('Y/m/d');
        }

        if ($invoiceDate instanceof DateTimeInterface) {
            return Carbon::instance($invoiceDate)->format('Y/m/d');
        }

        try {
            return Carbon::parse($invoiceDate)->format('Y/m/d');
        } catch (Throwable) {
            return now()->format('Y/m/d');
        }
    }
}
