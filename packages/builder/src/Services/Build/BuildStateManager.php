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

        DB::table('builder_builds')->insert([
            'entity_id' => $entityId,
            'build_context' => $contextType,
            'files' => json_encode($files),
            'blocks' => json_encode($blocks),
            'created_at' => now(),
        ]);

        $this->currentState = [
            'entity_id' => $entityId,
            'build_context' => $contextType,
            'files' => $files,
            'blocks' => $blocks,
        ];
    }

    protected function loadCurrentState(): void
    {
        $entityId = $this->getEntityId();
        $contextType = $this->context->getContextType();

        $build = DB::table('builder_builds')
            ->where('entity_id', $entityId)
            ->where('build_context', $contextType)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($build) {
            $this->currentState = [
                'entity_id' => $build->entity_id,
                'build_context' => $build->build_context,
                'files' => json_decode($build->files, true),
                'blocks' => json_decode($build->blocks, true),
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
