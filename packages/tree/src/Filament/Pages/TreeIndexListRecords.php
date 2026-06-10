<?php

declare(strict_types=1);

namespace Moox\Tree\Filament\Pages;

use Filament\Actions\Action;
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
        $this->clearTreeSelectionUnlessVisibleInCurrentQuery();
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
        return [
            Action::make('create')
                ->label(fn (): string => __('filament-actions::create.single.label', [
                    'label' => static::getResource()::getModelLabel(),
                ]))
                ->action(fn (): mixed => $this->dispatch('tree-index-create-root'))
                ->hidden(fn (): bool => $this->shouldHideTreeCreateHeaderAction()),
        ];
    }

    protected function shouldHideTreeCreateHeaderAction(): bool
    {
        return property_exists($this, 'activeTab')
            && ($this->activeTab ?? null) === 'deleted';
    }
}
