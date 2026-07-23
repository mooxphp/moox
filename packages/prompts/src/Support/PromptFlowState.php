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
    ) {
    }

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

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'flowId' => $this->flowId,
            'commandName' => $this->commandName,
            'steps' => $this->steps,
            'currentIndex' => $this->currentIndex,
            'stepOutputs' => $this->stepOutputs,
            'context' => $this->context,
            'completed' => $this->completed,
            'failedAt' => $this->failedAt,
            'errorMessage' => $this->errorMessage,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            flowId: (string) $data['flowId'],
            commandName: (string) $data['commandName'],
            steps: (array) $data['steps'],
            currentIndex: (int) ($data['currentIndex'] ?? 0),
            stepOutputs: (array) ($data['stepOutputs'] ?? []),
            context: (array) ($data['context'] ?? []),
            completed: (bool) ($data['completed'] ?? false),
            failedAt: isset($data['failedAt']) ? (string) $data['failedAt'] : null,
            errorMessage: isset($data['errorMessage']) ? (string) $data['errorMessage'] : null,
        );
    }
}
