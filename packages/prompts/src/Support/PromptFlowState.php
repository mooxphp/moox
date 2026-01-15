<?php

namespace Moox\Prompts\Support;

class PromptFlowState
{
    public function __construct(
        public string $flowId,
        public string $commandName,
        public array $steps,
        public int $currentIndex = 0,
        public array $stepOutputs = [],
        public array $context = [],
        public bool $completed = false,
        public ?string $failedAt = null,
        public ?string $errorMessage = null,
    ) {}

    public function nextPendingStep(): ?string
    {
        if ($this->completed) {
            return null;
        }

        return $this->steps[$this->currentIndex] ?? null;
    }

    public function markStepFinished(string $step, string $output = ''): void
    {
        $this->stepOutputs[$step] = $output;
        $this->currentIndex++;
        if ($this->currentIndex >= count($this->steps)) {
            $this->completed = true;
        }
    }

    public function markFailed(string $step, string $message): void
    {
        $this->failedAt = $step;
        $this->errorMessage = $message;
    }
}
