<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class Constraint
{
    /**
     * @param  list<string>  $fields
     */
    private function __construct(
        private readonly string $kind,
        private readonly array $fields,
    ) {
    }

    /**
     * @param  array<array-key, string>  $fields
     */
    public static function unique(array $fields): self
    {
        return new self('unique', array_values($fields));
    }

    /**
     * @param  array<array-key, string>  $fields
     */
    public static function index(array $fields): self
    {
        return new self('index', array_values($fields));
    }

    public function isUnique(): bool
    {
        return $this->kind === 'unique';
    }

    public function isIndex(): bool
    {
        return $this->kind === 'index';
    }

    /**
     * @return list<string>
     */
    public function fields(): array
    {
        return $this->fields;
    }
}
