<?php

declare(strict_types=1);

namespace Moox\EBilling\Support;

use Moox\EBilling\Models\EbillingDocument;

final class SeverityReleaseSnapshotCollector
{
    /**
     * @return list<array{field: string, line_id: string|null, reason: string, released_by_id: mixed, released_at: string}>
     */
    public static function collect(EbillingDocument $document): array
    {
        $releases = is_array($document->severity_releases) ? $document->severity_releases : [];
        $forwarded = [];

        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        if (is_array($invoiceFields)) {
            foreach ($invoiceFields as $field => $priority) {
                if (! is_string($field) || $priority !== 'should') {
                    continue;
                }

                $entry = EbillingDocument::readSeverityReleaseEntry($releases, $field);
                if (! EbillingDocument::severityReleaseEntryIsValid($entry)) {
                    continue;
                }

                $forwarded[] = [
                    'field' => $field,
                    'line_id' => null,
                    'reason' => (string) ($entry['reason'] ?? ''),
                    'released_by_id' => $entry['released_by_id'] ?? null,
                    'released_at' => (string) ($entry['released_at'] ?? ''),
                ];
            }
        }

        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);
        $lines = is_array($releases['lines'] ?? null) ? $releases['lines'] : [];

        foreach ($lines as $lineId => $lineReleases) {
            if (! is_string($lineId) || ! is_array($lineReleases) || ! is_array($lineFields)) {
                continue;
            }

            foreach ($lineFields as $field => $priority) {
                if (! is_string($field) || $priority !== 'should') {
                    continue;
                }

                $entry = EbillingDocument::readSeverityReleaseEntry($releases, $field, $lineId);
                if (! EbillingDocument::severityReleaseEntryIsValid($entry)) {
                    continue;
                }

                $forwarded[] = [
                    'field' => $field,
                    'line_id' => $lineId,
                    'reason' => (string) ($entry['reason'] ?? ''),
                    'released_by_id' => $entry['released_by_id'] ?? null,
                    'released_at' => (string) ($entry['released_at'] ?? ''),
                ];
            }
        }

        return $forwarded;
    }
}
