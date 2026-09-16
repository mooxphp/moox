<?php

declare(strict_types=1);

namespace Moox\Mjml;

class MjmlError
{
    /**
     * @param  array<string, mixed>  $rawError
     */
    public function __construct(
        protected array $rawError,
    ) {}

    public function line(): int
    {
        return (int) ($this->rawError['line'] ?? 0);
    }

    public function message(): string
    {
        return (string) ($this->rawError['message'] ?? '');
    }

    public function tagName(): string
    {
        return (string) ($this->rawError['tagName'] ?? '');
    }

    public function formattedMessage(): string
    {
        return "Line {$this->line()}: {$this->message()}";
    }

    public function __toString(): string
    {
        return $this->formattedMessage();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->rawError;
    }
}
