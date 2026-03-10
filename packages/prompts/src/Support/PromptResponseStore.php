<?php

namespace Moox\Prompts\Support;

class PromptResponseStore
{
    protected array $responses = [];

    protected int $promptCounter = 0;

    public function set(string $promptId, mixed $value): void
    {
        $this->responses[$promptId] = $value;
    }

    public function get(string $promptId): mixed
    {
        return $this->responses[$promptId] ?? null;
    }

    public function has(string $promptId): bool
    {
        return isset($this->responses[$promptId]);
    }

    public function clear(): void
    {
        $this->responses = [];
        $this->promptCounter = 0;
    }

    public function all(): array
    {
        return $this->responses;
    }

    public function getNextPromptId(string $method): string
    {
        return 'prompt_'.(++$this->promptCounter);
    }

    public function resetCounter(): void
    {
        $this->promptCounter = 0;
    }

    public function setCounter(int $count): void
    {
        $this->promptCounter = $count;
    }
}
