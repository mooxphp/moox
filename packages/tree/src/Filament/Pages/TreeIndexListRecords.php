<?php

declare(strict_types=1);

namespace Heco\FilamentTreeIndex\Filament\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Heco\FilamentTreeIndex\Config\TreeIndexConfigurationRegistry;
use Heco\FilamentTreeIndex\Contracts\ConfiguresTreeIndex;

abstract class TreeIndexListRecords extends ListRecords
{
    protected string $view = 'filament-tree-index::filament.pages.tree-index';

    public string $treeIndexConfigurationKey = '';

    public function mount(): void
    {
        parent::mount();

        $resource = static::getResource();

        if (! is_a($resource, ConfiguresTreeIndex::class, true)) {
            abort(500, 'Resource must implement ConfiguresTreeIndex.');
        }

        $this->treeIndexConfigurationKey = $resource;

        TreeIndexConfigurationRegistry::register(
            $this->treeIndexConfigurationKey,
            $resource::treeIndex(),
        );
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'treeIndexConfigurationKey' => $this->treeIndexConfigurationKey,
        ];
    }
}
