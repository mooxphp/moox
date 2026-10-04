<?php

declare(strict_types=1);

namespace Moox\Definition\Filament;

final class Presentation
{
    /** @var array<string, string> */
    private array $titleAttributes = [];

    public function relation(string $name, string $titleAttribute): self
    {
        $copy = clone $this;
        $copy->titleAttributes[$name] = $titleAttribute;

        return $copy;
    }

    public function titleAttribute(string $relation): ?string
    {
        return $this->titleAttributes[$relation] ?? null;
    }
}
