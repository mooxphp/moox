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
    ): void {
        $bulk = $this->bulkConfig($definition);
        $persistChildren = $this->persistChildren($definition);
        $writeStrategy = $this->writeStrategy($definition);

        $stats = [
            'total' => 0,
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'failures' => [],
        ];

        foreach ($projectionChunks as $chunk) {
            if ($chunk === []) {
                continue;
            }

            $stats['total'] += count($chunk);

            if ($writeStrategy === 'batch' && ! $persistChildren && $processBatchChunk !== null) {
                foreach ($processBatchChunk($chunk) as $result) {
                    $this->accumulateResult($stats, $result);
                }

                continue;
            }

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

                    $this->accumulateResult(
                        $stats,
                        new BulkItemResult(
                            status: (string) $child->status,
                            errorMessage: $child->error_message,
                            destinationKey: $child->destination_key !== null ? (string) $child->destination_key : null,
                            sourceLabel: BulkTransformSummaryFormatter::projectionSourceLabel(
                                is_array($child->source_projection) ? $child->source_projection : [],
                                is_array($definition->destination_match) ? $definition->destination_match : [],
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

        $failed = $stats['failed'];
        $parent->forceFill([
            'status' => $failed === 0 ? 'processed' : 'failed',
            'validation_status' => $failed === 0 ? 'valid' : 'invalid',
            'degraded' => $failed > 0,
            'bulk_stats' => $stats,
            'error_message' => BulkTransformSummaryFormatter::formatMessage($stats),
            'last_success_at' => $failed === 0 ? now() : null,
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
    private function accumulateResult(array &$stats, BulkItemResult $result, ?int $transformRecordId = null): void
    {
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

        if ($transformRecordId !== null) {
            $failure['transform_record_id'] = $transformRecordId;
        }

        $stats['failures'][] = $failure;
    }
}
