<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Support\Arr;
use Moox\Transform\Models\TransformRecord;

final class BulkTransformSummaryFormatter
{
    /**
     * @param  array<string, mixed>  $stats
     */
    public static function formatMessage(array $stats): string
    {
        $total = (int) ($stats['total'] ?? 0);
        $processed = (int) ($stats['processed'] ?? 0);
        $updated = (int) ($stats['updated'] ?? 0);
        $skipped = (int) ($stats['skipped'] ?? 0);
        $failed = (int) ($stats['failed'] ?? 0);

        if ($total === 0) {
            return 'Bulk transform completed with no projections.';
        }

        $parts = array_filter([
            $processed > 0 ? "{$processed} created" : null,
            $updated > 0 ? "{$updated} updated" : null,
            $skipped > 0 ? "{$skipped} skipped" : null,
            $failed > 0 ? "{$failed} failed" : null,
        ]);

        $summary = $parts !== [] ? implode(', ', $parts) : '0 changes';

        if ($failed === 0) {
            return "Bulk transform completed: {$total} projections ({$summary}).";
        }

        $failures = is_array($stats['failures'] ?? null) ? $stats['failures'] : [];
        $failureDetails = self::formatFailures($failures, $failed);

        return "Bulk transform completed with failures: {$total} projections ({$summary}).{$failureDetails}";
    }

    public static function formatForDisplay(TransformRecord $record): string
    {
        $stats = $record->bulk_stats;
        if (! is_array($stats) || $stats === []) {
            return (string) ($record->error_message ?? '');
        }

        $lines = [
            self::formatMessage($stats),
            '',
            'Counts:',
            '  total: '.(int) ($stats['total'] ?? 0),
            '  created: '.(int) ($stats['processed'] ?? 0),
            '  updated: '.(int) ($stats['updated'] ?? 0),
            '  skipped: '.(int) ($stats['skipped'] ?? 0),
            '  failed: '.(int) ($stats['failed'] ?? 0),
        ];

        $failures = is_array($stats['failures'] ?? null) ? $stats['failures'] : [];
        if ($failures !== []) {
            $lines[] = '';
            $lines[] = 'Failure samples:';
            foreach ($failures as $index => $failure) {
                if (! is_array($failure)) {
                    continue;
                }

                $lines[] = self::formatFailureLine($index + 1, $failure);
            }

            $failed = (int) ($stats['failed'] ?? 0);
            if ($failed > count($failures)) {
                $lines[] = '  … and '.($failed - count($failures)).' more (see child transform records or logs).';
            }
        }

        if (is_string($record->error_message) && $record->error_message !== '' && ! in_array($record->error_message, $lines, true)) {
            $lines[] = '';
            $lines[] = 'Message:';
            $lines[] = $record->error_message;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $definitionDestinationMatch
     */
    public static function projectionSourceLabel(array $projection, array $definitionDestinationMatch): ?string
    {
        foreach ($definitionDestinationMatch as $field => $path) {
            if (! is_string($field) || $field === '' || ! is_string($path) || $path === '') {
                continue;
            }

            $value = data_get($projection, $path);
            if ($value === null || $value === '') {
                continue;
            }

            return "{$field}={$value}";
        }

        $fallbackKeys = ['sku', 'code', 'external_key', 'id'];
        foreach ($fallbackKeys as $key) {
            $value = data_get($projection, $key);
            if ($value !== null && $value !== '') {
                return "{$key}={$value}";
            }
        }

        $flat = Arr::dot($projection);
        foreach ($flat as $path => $value) {
            if (! is_scalar($value) || (string) $value === '') {
                continue;
            }

            if (preg_match('/(sku|code|nummer|id|key)$/i', (string) $path) === 1) {
                return "{$path}={$value}";
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $failures
     */
    public static function formatFailures(array $failures, int $failedTotal): string
    {
        if ($failures === []) {
            return $failedTotal > 0 ? ' Open the transform record for failure samples.' : '';
        }

        $lines = ["\n\nFailure samples:"];
        foreach ($failures as $index => $failure) {
            if (! is_array($failure)) {
                continue;
            }

            $lines[] = self::formatFailureLine($index + 1, $failure);
        }

        if ($failedTotal > count($failures)) {
            $lines[] = '… and '.($failedTotal - count($failures)).' more.';
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $failure
     */
    private static function formatFailureLine(int $number, array $failure): string
    {
        $parts = ["  {$number}."];

        if (is_string($failure['source_label'] ?? null) && $failure['source_label'] !== '') {
            $parts[] = $failure['source_label'];
        }

        if (is_string($failure['status'] ?? null) && $failure['status'] !== '') {
            $parts[] = '['.$failure['status'].']';
        }

        if (isset($failure['transform_record_id'])) {
            $parts[] = 'record #'.$failure['transform_record_id'];
        }

        if (is_string($failure['error_message'] ?? null) && $failure['error_message'] !== '') {
            $parts[] = '— '.$failure['error_message'];
        }

        return implode(' ', $parts);
    }
}
