<?php

declare(strict_types=1);

namespace Moox\Definition\Filament;

use Filament\Schemas\Schema;
use Filament\Tables\Table as FilamentTable;

final class Page
{
    public function __construct(
        private readonly Resource $resource,
    ) {
    }

    public function resource(): Resource
    {
        return $this->resource;
    }

    public function applyForm(Schema $schema): Schema
    {
        return $this->resource->applyForm($schema);
    }

    public function applyTable(FilamentTable $table): FilamentTable
    {
        return $this->resource->applyTable($table);
    }
}
