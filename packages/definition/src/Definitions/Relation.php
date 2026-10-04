<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

final class Relation
{
    public function __construct(
        private readonly string $name,
        private readonly RelationType $type,
        private readonly ?EntityReference $target,
        private readonly ?EntityReference $through,
        private readonly RelationKeys $keys,
        private readonly ?Pivot $pivot,
        private readonly ?string $inverse,
        private readonly bool $nullable,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): RelationType
    {
        return $this->type;
    }

    public function target(): ?EntityReference
    {
        return $this->target;
    }

    public function through(): ?EntityReference
    {
        return $this->through;
    }

    public function keys(): RelationKeys
    {
        return $this->keys;
    }

    public function pivot(): ?Pivot
    {
        return $this->pivot;
    }

    public function inverse(): ?string
    {
        return $this->inverse;
    }

    public function nullable(): bool
    {
        return $this->nullable;
    }
}
