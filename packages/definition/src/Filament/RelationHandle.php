<?php

declare(strict_types=1);

namespace Moox\Definition\Filament;

use Filament\Forms\Components\Select;

final class RelationHandle
{
    public function __construct(
        private readonly Resource $resource,
        private readonly string $name,
    ) {
    }

    public function formComponent(): Select
    {
        return $this->resource->relationFormComponent($this->name);
    }

    public function replaceForm(Select $component): self
    {
        $this->resource->replaceRelationForm($this->name, $component);

        return $this;
    }
}
