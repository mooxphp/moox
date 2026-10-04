<?php

declare(strict_types=1);

namespace Moox\Definition\Definitions;

abstract class Feature
{
    abstract public static function key(): string;

    abstract public function apply(Entity $entity): void;

    /**
     * @return list<string>
     */
    public function overrides(): array
    {
        return [];
    }
}
