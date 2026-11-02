<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Execution;

use Illuminate\Support\Facades\Config;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;

final class BulkTransformExecutor
{
    /**
     * @param  callable(TransformRecord): void  $processRecord
     * @param  iterable<int, list<array<string, mixed>>>  $projectionChunks
     * @param  callable(array<string, mixed>): BulkItemResult|null  $processInlineProjection
     * @param  callable(list<array<string, mixed>>): list<BulkItemResult>|null  $processBatchChunk
     */
    public function run(
        TransformRecord $parent,
        TransformDefinition $definition,
        iterable $projectionChunks,
        callable $processRecord,
        ?callable $processInlineProjection = null,
        ?callable $processBatchChunk = null,
        ?int $expectedTotal = null,
    ): void {
        $persistChildren = $this->persistChildren($definition);
        $writeStrategy = $this->writeStrategy($definition);
        $persistEvery = max(1, (int) Config::get('transform.bulk.progress_persist_every', 1));

        $existingStats = is_array($parent->bulk_stats) ? $parent->bulk_stats : [];
        $stats = [
            'total' => 0,
            'expected_total' => $expectedTotal,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failures' => [],
            'progress' => 0,
            'external_id_count' => 0,
            'matched_external_id_count' => 0,
            'external_ids' => [],
        ];
        if (is_array($existingStats['delta'] ?? null)) {
            $stats['delta'] = $existingStats['delta'];
        }

        $this->persistIntermediateStats($parent, $stats);

        $chunksSincePersist = 0;

        foreach ($projectionChunks as $chunk) {
            if ($chunk === []) {
                continue;
            }

            $stats['total'] += count($chunk);

            if ($writeStrategy === 'batch' && ! $persistChildren && $processBatchChunk !== null) {
                foreach ($processBatchChunk($chunk) as $result) {
                    $this->accumulateResult($stats, $result);
                }
            } else {
                foreach ($chunk as $projection) {
                    if ($persistChildren) {
                        $child = TransformRecord::query()->create([
                            'transform_definition_id' => $definition->id,
                            'parent_transform_record_id' => $parent->id,
                            'source_projection' => $projection,
                            'source_references' => [],
                        ]);

                        $processRecord($child);
                        $child->refresh();

                        $childProjection = is_array($child->source_projection) ? $child->source_projection : [];
                        $failureContext = BulkTransformSummaryFormatter::failureContext(
                            $definition,
                            $childProjection,
                        );

                        $this->accumulateResult(
                            $stats,
                            new BulkItemResult(
                                status: (string) $child->status,
                                errorMessage: $child->error_message,
                                destinationKey: $child->destination_key !== null ? (string) $child->destination_key : null,
                                sourceLabel: $failureContext['source_label'],
                                sourceReference: $failureContext['source_reference'],
                                externalId: BulkTransformSummaryFormatter::externalId(
                                    $childProjection,
                                    $this->destinationMatch($definition),
                                ),
                            ),
                            (int) $child->id,
                        );

                        continue;
                    }

                    if ($processInlineProjection === null) {
                        throw new \RuntimeException('Inline bulk processing requires an inline projection processor.');
                    }

                    $result = $processInlineProjection($projection);
                    if ($result instanceof BulkItemResult) {
                        $this->accumulateResult($stats, $result);
                    }
                }
            }

            $chunksSincePersist++;
            if ($chunksSincePersist >= $persistEvery) {
                $this->persistIntermediateStats($parent, $stats);
                $chunksSincePersist = 0;
            }
        }

        $stats['progress'] = BulkTransformSummaryFormatter::progressPercent($stats, completed: true);
        BulkTransformSummaryFormatter::rememberDuration($stats, $parent);
        $completion = BulkTransformSummaryFormatter::completion($stats);

        $parent->forceFill([
            'status' => $completion['status'],
            'validation_status' => $completion['validation_status'],
            'degraded' => $completion['degraded'],
            'bulk_stats' => $stats,
            'error_message' => BulkTransformSummaryFormatter::formatMessage($stats),
            'last_success_at' => $completion['status'] === 'processed' ? now() : null,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function bulkConfig(TransformDefinition $definition): array
    {
        $bulk = $definition->getAttribute('bulk');

        return is_array($bulk) ? $bulk : [];
    }

    private function persistChildren(TransformDefinition $definition): bool
    {
        $bulk = $this->bulkConfig($definition);

        return (bool) ($bulk['persist_children'] ?? Config::get('transform.bulk.persist_children', true));
    }

    private function writeStrategy(TransformDefinition $definition): string
    {
        $bulk = $this->bulkConfig($definition);

        return (string) ($bulk['write_strategy'] ?? Config::get('transform.bulk.write_strategy', 'row'));
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function persistIntermediateStats(TransformRecord $parent, array &$stats): void
    {
        $stats['progress'] = BulkTransformSummaryFormatter::progressPercent($stats, completed: false);

        $parent->forceFill([
            'bulk_stats' => $stats,
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function destinationMatch(TransformDefinition $definition): array
    {
        $match = $definition->getAttribute('destination_match');

        return is_array($match) ? $match : [];
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function accumulateResult(array &$stats, BulkItemResult $result, ?int $transformRecordId = null): void
    {
        $this->accumulateExternalId($stats, $result);

        if ($result->status === 'processed') {
            $stats['processed']++;

            return;
        }

        if ($result->status === 'updated') {
            $stats['updated']++;

            return;
        }

        if ($result->status === 'skipped') {
            $stats['skipped']++;

            return;
        }

        $stats['failed']++;
        $maxFailures = (int) Config::get('transform.bulk.max_failure_samples', 50);
        if ($maxFailures > 0 && count($stats['failures']) >= $maxFailures) {
            return;
        }

        $failure = [
            'status' => $result->status,
            'error_message' => $result->errorMessage,
        ];

        if ($result->sourceLabel !== null && $result->sourceLabel !== '') {
            $failure['source_label'] = $result->sourceLabel;
        }

        if ($result->sourceReference !== null && $result->sourceReference !== '') {
            $failure['source_reference'] = $result->sourceReference;
        }

        if ($transformRecordId !== null) {
            $failure['transform_record_id'] = $transformRecordId;
        }

        $stats['failures'][] = $failure;
    }

    /**
     * @param  array<string, mixed>  $stats
     */
    private function accumulateExternalId(array &$stats, BulkItemResult $result): void
    {
        $externalId = $result->externalId;
        if (! is_string($externalId) || $externalId === '') {
            return;
        }

        $stats['external_id_count'] = (int) ($stats['external_id_count'] ?? 0) + 1;
        if ($result->status === 'updated') {
            $stats['matched_external_id_count'] = (int) ($stats['matched_external_id_count'] ?? 0) + 1;
        }

        $maxSamples = (int) Config::get('transform.bulk.max_external_id_samples', 50);
        $samples = is_array($stats['external_ids'] ?? null) ? $stats['external_ids'] : [];
        if ($maxSamples <= 0 || count($samples) >= $maxSamples) {
            return;
        }

        $sample = [
            'id' => $externalId,
            'status' => $result->status,
        ];
        if (is_string($result->sourceLabel) && $result->sourceLabel !== '') {
            $sample['label'] = $result->sourceLabel;
        }

        $samples[] = $sample;
        $stats['external_ids'] = $samples;
    }
}
