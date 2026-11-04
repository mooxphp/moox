<?php

declare(strict_types=1);

namespace Moox\Transform\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Moox\Transform\Models\TransformDefinition;
use Moox\Transform\Models\TransformRecord;
use Moox\Transform\Support\TransformQueues;

final class RunScheduledTransformDefinitionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $transformDefinitionId)
    {
        $this->onQueue(TransformQueues::dispatch());
    }

    public function handle(): void
    {
        $definition = TransformDefinition::query()->find($this->transformDefinitionId);
        if (! $definition instanceof TransformDefinition || ! $definition->is_active) {
            return;
        }

        $sourceReferences = $definition->source_references;
        $record = TransformRecord::query()->create([
            'transform_definition_id' => $definition->getKey(),
            'source_references' => is_array($sourceReferences) ? $sourceReferences : [],
            'status' => 'pending',
            'validation_status' => 'pending',
        ]);

        RunTransformRecordJob::dispatchForActor((int) $record->getKey());
    }
}
