<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleItemResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Moox\Builder\Models\SimpleItem;
use Moox\Builder\Resources\SimpleItemResource;
use Moox\Core\Traits\TabsInPage;

class ListSimpleItems extends ListRecords
{
    use TabsInPage;

    public static string $resource = SimpleItemResource::class;

    public function mount(): void
    {
        parent::mount();
        static::getResource()::setCurrentTab($this->activeTab);
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function updatedActiveTab(): void
    {
        static::getResource()::setCurrentTab($this->activeTab);
        $this->tableFilters = null;
        $this->tableSortColumn = null;
        $this->tableSortDirection = null;
        $this->resetTable();
    }

    protected function getTableQuery(): Builder
    {
        return static::getResource()::getTableQuery($this->activeTab);
    }

    public function getTitle(): string
    {
        return config('builder.resources.simple-item.plural');
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.simple-item.tabs', SimpleItem::class);
    }
}
