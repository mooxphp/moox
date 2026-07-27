<?php

declare(strict_types=1);

namespace Moox\Transform\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\ConfiguredImportRecordProjectionEnricher;

final class DispatchTransformDefinitionForEndpointJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 0;

    public function __construct(
        private readonly int $transformDefinitionId,
        private readonly int $endpointId,
        private readonly ?int $startingAfterId = null,
    ) {
        $timeout = config('transform.job_timeout', 0);
        $this->timeout = is_int($timeout) ? $timeout : 0;
        $this->onQueue((string) config('transform.job_queue', 'transform'));
    }

    public function handle(): void
    {
        $definition = TransformDefinition::query()->find($this->transformDefinitionId);
        if (! $definition instanceof TransformDefinition || ! $definition->is_active) {
            return;
        }

        $importRecordModel = config('transform.import_record_model');
        if (! is_string($importRecordModel) || $importRecordModel === '' || ! class_exists($importRecordModel) || ! is_subclass_of($importRecordModel, Model::class)) {
            return;
        }

        $foreignKey = config('transform.import_record_endpoint_foreign_key', 'api_endpoint_id');
        if (! is_string($foreignKey) || $foreignKey === '') {
            $foreignKey = 'api_endpoint_id';
        }

        $chunkSize = max(1, (int) config('transform.bulk_dispatch.chunk_size', 100));

        /** @var Model $prototype */
        $prototype = new $importRecordModel;
        $keyName = $prototype->getKeyName();
        $processedInChunk = 0;
        $lastId = null;

        $query = $prototype->newQuery()
            ->where($foreignKey, $this->endpointId)
            ->orderBy($keyName);

        if ($this->startingAfterId !== null) {
            $query->where($keyName, '>', $this->startingAfterId);
        }

        $query->limit($chunkSize)->each(function (Model $importRecord) use ($definition, &$processedInChunk, &$lastId): void {
            $transformRecord = TransformRecord::query()->create([
                'transform_definition_id' => $definition->getKey(),
                'source_projection' => $this->buildRunSourceProjection($definition, (int) $importRecord->getKey()),
                'source_references' => $definition->source_references,
                'status' => 'pending',
                'validation_status' => 'pending',
            ]);

            RunTransformRecordJob::dispatch((int) $transformRecord->getKey());
            $processedInChunk++;
            $lastId = (int) $importRecord->getKey();
        });

        if ($processedInChunk === $chunkSize && $lastId !== null) {
            self::dispatch($this->transformDefinitionId, $this->endpointId, $lastId);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRunSourceProjection(TransformDefinition $definition, int $importRecordId): array
    {
        $configured = config('transform.default_source_projection');
        $projection = is_array($configured) ? $configured : [];

        $contextKey = config('transform.import_record_context_key', 'import_record_id');
        if (! is_string($contextKey) || $contextKey === '') {
            $contextKey = 'import_record_id';
        }
        $context = is_array($projection['context'] ?? null) ? $projection['context'] : [];
        $context[$contextKey] = $importRecordId;
        $projection['context'] = $context;

        return app(ConfiguredImportRecordProjectionEnricher::class)->enrich($importRecordId, $projection);
    }
}
