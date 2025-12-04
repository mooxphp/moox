<?php

namespace Moox\Prompts\Support;

use Exception;

class PendingPromptsException extends Exception
{
    protected array $prompts = [];

    public function __construct(array $prompt)
    {
        parent::__construct('Pending prompt requires user input');
        $this->prompts = [$prompt];
    }

    public function getPrompts(): array
    {
        return $this->prompts;
    }

    public function getPrompt(): ?array
    {
        return $this->prompts[0] ?? null;
    }
}
