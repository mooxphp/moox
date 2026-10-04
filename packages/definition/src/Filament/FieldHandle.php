<?php

declare(strict_types=1);

namespace Moox\Definition\Filament;

use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;

final class FieldHandle
{
    public function __construct(
        private readonly Resource $resource,
        private readonly string $name,
    ) {
    }

    public function formComponent(): Component
    {
        return $this->resource->formComponent($this->name);
    }

    public function tableColumn(): Column
    {
        return $this->resource->tableColumn($this->name);
    }

    public function replaceForm(Component $component): self
    {
        $this->resource->replaceForm($this->name, $component);

        return $this;
    }

    public function replaceTable(Column $column): self
    {
        $this->resource->replaceTable($this->name, $column);

        return $this;
    }
}
