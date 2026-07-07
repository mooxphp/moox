<?php

declare(strict_types=1);

namespace Moox\Transform\Support\Expansion;

use Moox\Transform\Enums\TransformExecutionMode;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\DbTableSourceQuery;

final class ExpandTransformExecutor
{
    public function __construct(
        private readonly TransformProjectionExpander $projectionExpander,
    ) {}

    /**
     * @param  callable(TransformRecord): void  $processRecord
     */
    public function run(TransformRecord $parent, TransformDefinition $definition, callable $processRecord): bool
    {
        if ($parent->parent_transform_record_id !== null) {
            return false;
        }

        $projections = $this->projectionExpander->expand($parent, $definition);

        if ($projections === []) {
            $parent->forceFill([
                'status' => 'skipped',
                'validation_status' => 'pending',
                'degraded' => true,
                'error_message' => 'No source projections found for expansion.',
            ])->save();

            return true;
        }

        if (count($projections) === 1 && ! $this->hasIterableSourceReference($parent, $definition)) {
            return false;
        }

        $failed = 0;

        foreach ($projections as $projection) {
            $child = TransformRecord::query()->create([
                'transform_definition_id' => $definition->id,
                'parent_transform_record_id' => $parent->id,
                'source_projection' => $projection,
                'source_references' => [],
            ]);

            $processRecord($child);

            $status = (string) $child->fresh()?->status;
            if (! in_array($status, ['processed', 'updated', 'skipped'], true)) {
                $failed++;
            }
        }

        $parent->forceFill([
            'status' => $failed === 0 ? 'processed' : 'failed',
            'validation_status' => $failed === 0 ? 'valid' : 'invalid',
            'degraded' => $failed > 0,
            'bulk_stats' => [
                'total' => count($projections),
                'failed' => $failed,
                'mode' => TransformExecutionMode::Expand->value,
            ],
            'error_message' => $failed === 0
                ? 'Expanded iteration into '.count($projections).' transform records.'
                : 'Expanded iteration into '.count($projections)." transform records with {$failed} failures.",
            'last_success_at' => $failed === 0 ? now() : null,
        ])->save();

        return true;
    }

    private function hasIterableSourceReference(TransformRecord $record, TransformDefinition $definition): bool
    {
        $definitionReferences = $this->arrayAttribute($definition, 'source_references');
        $runtimeReferences = $this->arrayAttribute($record, 'source_references');
        $references = $runtimeReferences !== [] ? $runtimeReferences : $definitionReferences;

        foreach ($references as $reference) {
            if (! is_array($reference)) {
                continue;
            }

            $sourceType = $reference['source_type'] ?? null;
            if (! in_array($sourceType, ['db_table', 'api_import_record'], true)) {
                continue;
            }

            if (
                ! DbTableSourceQuery::hasRowKey($reference['row_key'] ?? null)
                && ! DbTableSourceQuery::hasRowKeyFrom($reference['row_key_from'] ?? null)
            ) {
                return true;
            }
        }

        $expand = $definition->getAttribute('expand');

        return is_array($expand) && $expand !== [];
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayAttribute(TransformDefinition|TransformRecord $model, string $attribute): array
    {
        $value = $model->getAttribute($attribute);

        return is_array($value) ? $value : [];
    }
}
