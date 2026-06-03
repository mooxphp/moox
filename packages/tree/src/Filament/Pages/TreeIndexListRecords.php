<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Moox\Tree\Contracts\ConfiguresTreeIndex;
use Moox\Tree\Filament\Concerns\InteractsWithTreeIndexListPage;

abstract class TreeIndexListRecords extends ListRecords
{
    use InteractsWithTreeIndexListPage;

    public string $treeIndexConfigurationKey = '';

    public function mount(): void
    {
        parent::mount();

        $this->mountInteractsWithTreeIndexListPage();

        $resource = static::getResource();

        if (! is_a($resource, ConfiguresTreeIndex::class, true)) {
            abort(500, 'Resource must implement ConfiguresTreeIndex.');
        }

        $this->treeIndexConfigurationKey = $resource;

        $this->refreshTreeIndexConfiguration();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function hydrate(): void
    {
        $this->hydrateInteractsWithTreeIndexListPage();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
