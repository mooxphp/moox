<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Models\EbillingDocument;

final class SeverityReleaseSnapshotCollector
{
    /**
     * @return list<ForwardedSeverityRelease>
     */
    public static function collect(EbillingDocument $document): array
    {
        $releases = is_array($document->severity_releases) ? $document->severity_releases : [];
        $forwarded = [];

        $documentType = $document->profileDocumentType();
        foreach (FieldValidationProfile::invoiceFields($documentType) as $field => $priority) {
            if ($priority !== 'should') {
                continue;
            }

            $entry = EbillingDocument::readSeverityReleaseEntry($releases, $field);
            if (! EbillingDocument::severityReleaseEntryIsValid($entry)) {
                continue;
            }

            $forwarded[] = new ForwardedSeverityRelease(
                field: $field,
                lineId: null,
                reason: (string) ($entry['reason'] ?? ''),
                releasedById: $entry['released_by_id'] ?? null,
                releasedAt: (string) ($entry['released_at'] ?? ''),
            );
        }

        $lineFields = FieldValidationProfile::lineFields($documentType);
        $lines = is_array($releases['lines'] ?? null) ? $releases['lines'] : [];

        foreach ($lines as $lineId => $lineReleases) {
            if (! is_string($lineId) || ! is_array($lineReleases)) {
                continue;
            }

            foreach ($lineFields as $field => $priority) {
                if ($priority !== 'should') {
                    continue;
                }

                $entry = EbillingDocument::readSeverityReleaseEntry($releases, $field, $lineId);
                if (! EbillingDocument::severityReleaseEntryIsValid($entry)) {
                    continue;
                }

                $forwarded[] = new ForwardedSeverityRelease(
                    field: $field,
                    lineId: $lineId,
                    reason: (string) ($entry['reason'] ?? ''),
                    releasedById: $entry['released_by_id'] ?? null,
                    releasedAt: (string) ($entry['released_at'] ?? ''),
                );
            }
        }

        return $forwarded;
    }

    /**
     * @param  list<ForwardedSeverityRelease>  $forwarded
     */
    public static function formatAsApprovalReason(array $forwarded): ?string
    {
        if ($forwarded === []) {
            return null;
        }

        $parts = [];

        foreach ($forwarded as $entry) {
            $label = is_string($entry->lineId) && $entry->lineId !== ''
                ? "{$entry->field} (line {$entry->lineId})"
                : $entry->field;
            $parts[] = "{$label}: {$entry->reason}";
        }

        return implode('; ', $parts);
    }
}
