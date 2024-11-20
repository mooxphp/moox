<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Build;

use Moox\Builder\Services\ContextAwareService;

class BuildStateManager extends ContextAwareService
{
    public function __construct(
        private readonly BuildRecorder $buildRecorder
    ) {}

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

        $this->currentState = $this->buildRecorder->loadCurrentState($entityId, $contextType);
    }

    protected function getEntityId(): int
    {
        return $this->buildRecorder->getEntityIdFromName($this->context->getEntityName());
    }
}
