<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class UniqueScope
{
    /**
     * @param  list<string>  $fields
     */
    private function __construct(
        private readonly string $kind,
        private readonly array $fields,
    ) {
    }

    public static function global(): self
    {
        return new self('global', []);
    }

    public static function locale(string $field): self
    {
        return new self('locale', [$field]);
    }

    public static function parent(string $field): self
    {
        return new self('parent', [$field]);
    }

    public static function tenant(string $field): self
    {
        return new self('tenant', [$field]);
    }

    /**
     * @param  array<array-key, string>  $fields
     */
    public static function fields(array $fields): self
    {
        return new self('fields', array_values($fields));
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function isGlobal(): bool
    {
        return $this->kind === 'global';
    }

    /**
     * @return list<string>
     */
    public function columns(): array
    {
        return $this->fields;
    }
}
