<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Illuminate\Support\Facades\DB;
use Moox\Builder\Services\ContextAwareService;

class BuildStateManager extends ContextAwareService
{
    protected array $currentState = [];

    public function execute(): void
    {
        $this->ensureContextIsSet();
        $this->loadCurrentState();
    }

    public function getCurrentState(): array
    {
        return $this->currentState;
    }

    public function updateState(array $files, array $blocks): void
    {
        $entityId = $this->getEntityId();
        $contextType = $this->context->getContextType();

        DB::table('builder_entity_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $contextType,
            'data' => json_encode($blocks),
            'files' => json_encode($files),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->currentState = [
            'entity_id' => $entityId,
            'build_context' => $contextType,
            'data' => $blocks,
            'files' => $files,
        ];
    }

    protected function loadCurrentState(): void
    {
        $entityId = $this->getEntityId();
        $contextType = $this->context->getContextType();

        $build = DB::table('builder_entity_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $contextType)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($build) {
            $this->currentState = [
                'entity_id' => $build->entity_id,
                'build_context' => $build->build_context,
                'data' => json_decode($build->data, true),
                'files' => json_decode($build->files, true),
            ];
        }
    }

    protected function getEntityId(): int
    {
        $entity = DB::table('builder_entities')
            ->where('singular', $this->context->getEntityName())
            ->whereNull('deleted_at')
            ->first();

        return $entity ? $entity->id : 0;
    }
}
