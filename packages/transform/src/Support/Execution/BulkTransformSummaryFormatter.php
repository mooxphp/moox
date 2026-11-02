<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\SourceContextResolver;

final class BulkTransformSummaryFormatter
{
    /**
     * @param  array<string, mixed>  $stats
     */
    public static function doneCount(array $stats): int
    {
        return (int) ($stats['processed'] ?? 0)
            + (int) ($stats['updated'] ?? 0)
            + (int) ($stats['skipped'] ?? 0)
            + (int) ($stats['failed'] ?? 0);
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    public static function progressPercent(array $stats, bool $completed = false): int
    {
        if ($completed) {
            return 100;
        }

        $expected = $stats['expected_total'] ?? null;
        if (! is_numeric($expected) || (int) $expected <= 0) {
            return self::doneCount($stats) > 0 ? 1 : 0;
        }

        $percent = (int) floor((100 * self::doneCount($stats)) / (int) $expected);

        return min(99, max(0, $percent));
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    public static function progressLabel(array $stats, ?string $status = null): string
    {
        $done = self::doneCount($stats);
        $expected = $stats['expected_total'] ?? null;
        $progress = isset($stats['progress']) && is_numeric($stats['progress'])
            ? (int) $stats['progress']
            : self::progressPercent($stats, in_array($status, ['processed', 'updated', 'skipped', 'failed', 'failed_validation'], true));

        if (is_numeric($expected) && (int) $expected > 0) {
            return "{$progress}% ({$done}/".(int) $expected.')';
        }

        if ($done > 0) {
            return "{$done} done";
        }

        return "{$progress}%";
    }

    /**
     * A finished bulk run stays processed when any row was written or skipped.
     * Row-level invalid data is degraded, not a failed job. The job fails when
     * every row failed.
     *
     * @param  array<string, mixed>  $stats
     * @return array{status: string, validation_status: string, degraded: bool}
     */
    public static function completion(array $stats): array
    {
        $succeeded = (int) ($stats['processed'] ?? 0)
            + (int) ($stats['updated'] ?? 0)
            + (int) ($stats['skipped'] ?? 0);
        $failed = (int) ($stats['failed'] ?? 0);
        $completed = $failed === 0 || $succeeded > 0;

        return [
            'status' => $completed ? 'processed' : 'failed',
            'validation_status' => $completed ? 'valid' : 'invalid',
            'degraded' => $failed > 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    public static function formatMessage(array $stats, bool $withFailures = true): string
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

        if ($failed === 0 || ! $withFailures) {
            $prefix = $failed === 0
                ? 'Bulk transform completed'
                : 'Bulk transform completed with failures';

            return "{$prefix}: {$total} projections ({$summary}).";
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
            self::formatMessage($stats, withFailures: false),
        ];

        $duration = self::durationLabel($record);
        if ($duration !== null) {
            $lines[] = $duration;
        }

        array_push(
            $lines,
            '',
            'Counts:',
            '  total: '.(int) ($stats['total'] ?? 0),
            '  created: '.(int) ($stats['processed'] ?? 0),
            '  updated: '.(int) ($stats['updated'] ?? 0),
            '  skipped: '.(int) ($stats['skipped'] ?? 0),
            '  failed: '.(int) ($stats['failed'] ?? 0),
        );

        array_push($lines, ...self::formatDeltaSection($stats));

        $failures = is_array($stats['failures'] ?? null) ? $stats['failures'] : [];
        if ($failures !== []) {
            $lines[] = '';
            $lines[] = 'Fehler:';
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

        $storedMessage = is_string($record->error_message) ? $record->error_message : '';
        if ($storedMessage !== '' && $storedMessage !== self::formatMessage($stats) && ! in_array($storedMessage, $lines, true)) {
            $lines[] = '';
            $lines[] = 'Message:';
            $lines[] = $storedMessage;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    public static function rememberDuration(array &$stats, TransformRecord $record): void
    {
        $started = $record->last_run_at;
        if (! $started instanceof \DateTimeInterface) {
            return;
        }

        $seconds = now()->getTimestamp() - Carbon::parse($started)->getTimestamp();
        $stats['duration_seconds'] = max(0, $seconds);
    }

    public static function humanDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remain = $seconds % 60;
        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours.'h';
        }

        if ($minutes > 0) {
            $parts[] = $minutes.'m';
        }

        if ($remain > 0 || $parts === []) {
            $parts[] = $remain.'s';
        }

        return implode(' ', $parts);
    }

    private static function durationLabel(TransformRecord $record): ?string
    {
        $seconds = self::durationSeconds($record);
        if ($seconds === null) {
            return null;
        }

        $prefix = $record->status === 'processing' ? 'Elapsed' : 'Duration';

        return $prefix.': '.self::humanDuration($seconds);
    }

    private static function durationSeconds(TransformRecord $record): ?int
    {
        $stats = is_array($record->bulk_stats) ? $record->bulk_stats : [];
        if ($record->status !== 'processing' && is_numeric($stats['duration_seconds'] ?? null)) {
            return max(0, (int) $stats['duration_seconds']);
        }

        $started = $record->last_run_at;
        if (! $started instanceof Carbon) {
            return null;
        }

        $finished = $record->status === 'processing' ? now() : $record->last_success_at;
        if (! $finished instanceof Carbon) {
            return null;
        }

        $seconds = $finished->getTimestamp() - $started->getTimestamp();
        if ($seconds < 0) {
            return null;
        }

        return $seconds;
    }

    /**
     * @param  array<string, mixed>  $projection
     * @param  array<string, mixed>  $destinationMatch
     */
    public static function externalId(array $projection, array $destinationMatch): ?string
    {
        $path = $destinationMatch['external_reference'] ?? null;
        if (! is_string($path) || $path === '') {
            return null;
        }

        $base = explode('|', $path, 2)[0];
        $value = data_get($projection, $base);
        if (! is_scalar($value)) {
            return null;
        }

        $externalId = trim((string) $value);

        return $externalId === '' ? null : $externalId;
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return list<string>
     */
    private static function formatDeltaSection(array $stats): array
    {
        $delta = $stats['delta'] ?? null;
        if (! is_array($delta)) {
            return [];
        }

        $cutoff = is_string($delta['cutoff'] ?? null) ? $delta['cutoff'] : '';
        $source = is_string($delta['source'] ?? null) ? $delta['source'] : '';
        $tables = is_array($delta['tables'] ?? null)
            ? array_values(array_filter($delta['tables'], 'is_string'))
            : [];
        if ($source === 'full_scan') {
            $since = trim(($tables !== [] ? implode(', ', $tables).' ' : '').'full scan');
        } else {
            $since = trim(($tables !== [] ? implode(', ', $tables).' ' : '').$cutoff);
            if ($source !== '') {
                $since .= " ({$source})";
            }
        }

        $lines = [
            '',
            'Delta:',
            '  since: '.$since,
            'Found:',
            '  rows: '.(int) ($stats['total'] ?? 0),
            '  with external id: '.(int) ($stats['external_id_count'] ?? 0),
            '  matched existing external id: '.(int) ($stats['matched_external_id_count'] ?? 0),
        ];

        $samples = is_array($stats['external_ids'] ?? null) ? $stats['external_ids'] : [];
        if ($samples === []) {
            return $lines;
        }

        $lines[] = 'External ids:';
        $shown = 0;
        foreach ($samples as $sample) {
            if (! is_array($sample)) {
                continue;
            }

            $id = is_string($sample['id'] ?? null) ? $sample['id'] : '';
            $label = is_string($sample['label'] ?? null) && $sample['label'] !== '' ? $sample['label'] : $id;
            if ($label === '') {
                continue;
            }

            $shown++;
            $status = is_string($sample['status'] ?? null) ? $sample['status'] : '';
            $statusLabel = match ($status) {
                'processed' => 'created',
                'updated' => 'matched',
                'skipped' => 'skipped',
                default => $status,
            };
            $lines[] = '  '.$shown.'. '.$label.($statusLabel !== '' ? " [{$statusLabel}]" : '');
        }

        $count = (int) ($stats['external_id_count'] ?? 0);
        if ($count > $shown) {
            $lines[] = '  … and '.($count - $shown).' more';
        }

        return $lines;
    }

    /**
     * @param  array<string, mixed>  $projection
     * @return array{source_label: ?string, source_reference: ?string}
     */
    public static function failureContext(TransformDefinition $definition, array $projection): array
    {
        $destinationMatch = is_array($definition->destination_match) ? $definition->destination_match : [];
        $sourceReferences = is_array($definition->source_references) ? $definition->source_references : [];
        $sourceContext = app(SourceContextResolver::class)->resolve($sourceReferences, [], $projection);

        return [
            'source_label' => self::projectionSourceLabel($projection, $destinationMatch, $sourceReferences),
            'source_reference' => app(SourceContextResolver::class)->sourceReferenceLabel($sourceContext),
        ];
    }

    /**
     * @param  array<string, mixed>  $definitionDestinationMatch
     */
    public static function projectionSourceLabel(
        array $projection,
        array $definitionDestinationMatch,
        array $sourceReferences = [],
    ): ?string {
        $sourceKey = self::sourceKeyLabel($projection, $sourceReferences);
        if ($sourceKey !== null) {
            return $sourceKey;
        }

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

        $sourceReferenceLabel = app(SourceContextResolver::class)->sourceReferenceLabel(
            app(SourceContextResolver::class)->resolve($sourceReferences, [], $projection),
        );
        if ($sourceReferenceLabel !== null) {
            return $sourceReferenceLabel;
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
     * @param  list<mixed>  $sourceReferences
     */
    public static function sourceKeyLabel(array $projection, array $sourceReferences): ?string
    {
        $key = self::sourceKey($projection, $sourceReferences);
        if ($key === null) {
            return null;
        }

        return $key['column'].'='.$key['value'];
    }

    /**
     * @param  list<mixed>  $sourceReferences
     */
    public static function sourceKeyValue(array $projection, array $sourceReferences): ?string
    {
        $key = self::sourceKey($projection, $sourceReferences);

        return $key === null ? null : $key['value'];
    }

    /**
     * @param  list<mixed>  $sourceReferences
     * @return array{column: string, value: string}|null
     */
    public static function sourceKey(array $projection, array $sourceReferences): ?array
    {
        foreach ($sourceReferences as $reference) {
            if (! is_array($reference) || ($reference['source_type'] ?? null) !== 'db_table') {
                continue;
            }

            $keyColumn = $reference['key_column'] ?? null;
            if (! is_string($keyColumn) || $keyColumn === '') {
                continue;
            }

            $alias = $reference['alias'] ?? null;
            $path = is_string($alias) && $alias !== '' ? $alias.'.'.$keyColumn : $keyColumn;
            $value = data_get($projection, $path);
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }

            return [
                'column' => $keyColumn,
                'value' => trim((string) $value),
            ];
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

        $lines = ["\n\nFehler:"];
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

        $source = self::readableSourceLabel(
            is_string($failure['source_label'] ?? null) ? $failure['source_label'] : null,
        );
        if ($source !== null) {
            $parts[] = $source;
        }

        $message = self::humanizeErrorMessage(
            is_string($failure['error_message'] ?? null) ? $failure['error_message'] : '',
        );
        if ($message !== '') {
            $parts[] = '— '.$message;
        }

        return implode(' ', $parts);
    }

    private static function readableSourceLabel(?string $label): ?string
    {
        if ($label === null || $label === '' || str_contains($label, '\\')) {
            return null;
        }

        if (preg_match('/^([^=]+)=(.*)$/', $label, $matches) !== 1) {
            return $label;
        }

        $value = trim($matches[2]);
        if ($value === '' || str_contains($value, '\\')) {
            return null;
        }

        return $matches[1].' '.$value;
    }

    public static function humanizeErrorMessage(string $message): string
    {
        $message = trim(preg_replace('/^Validation failed\.?\s*/', '', $message) ?? $message);
        if ($message === '') {
            return '';
        }

        $parts = array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode('|', $message),
        )));
        if ($parts === []) {
            return $message;
        }

        $sentences = [];
        foreach ($parts as $part) {
            if (preg_match('/^([^:]+):\s*(.+)$/', $part, $matches) !== 1) {
                $sentences[] = $part;

                continue;
            }

            $sentences[] = self::humanizeFieldError(trim($matches[1]), trim($matches[2]));
        }

        return implode(' ', $sentences);
    }

    private static function humanizeFieldError(string $field, string $detail): string
    {
        $rules = preg_split('/\s+/', $detail) ?: [];
        foreach ($rules as $rule) {
            if (preg_match('/^(validation\.)?[a-z_]+(?:\.[a-z_]+)*$/', $rule) !== 1) {
                return 'Feld „'.$field.'“: '.$detail;
            }
        }

        $sentences = [];

        foreach ($rules as $rule) {
            $sentences[] = match ($rule) {
                'validation.required', 'required' => 'Feld „'.$field.'“ ist leer',
                'validation.string', 'string' => 'Feld „'.$field.'“ ist kein Text',
                'validation.integer', 'integer' => 'Feld „'.$field.'“ ist keine Zahl',
                'validation.email', 'email' => 'Feld „'.$field.'“ ist keine E-Mail',
                'validation.url', 'url' => 'Feld „'.$field.'“ ist keine Website',
                'validation.regex', 'regex' => 'Feld „'.$field.'“ hat ein ungültiges Format',
                'validation.max.string', 'validation.max' => 'Feld „'.$field.'“ ist zu lang',
                default => str_starts_with($rule, 'validation.')
                    ? 'Feld „'.$field.'“: '.substr($rule, strlen('validation.'))
                    : 'Feld „'.$field.'“: '.$rule,
            };
        }

        return implode(', ', $sentences);
    }
}
