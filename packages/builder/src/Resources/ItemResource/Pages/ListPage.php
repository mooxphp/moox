<?php

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\ItemResource;
use Moox\Builder\Resources\ItemResource\Widgets\ItemWidgets;
use Moox\Core\Traits\HasDynamicTabs;

class ListPage extends ListRecords
{
    use HasDynamicTabs;

    public static string $resource = ItemResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            ItemWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('builder::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Item {
                    return $model::create($data);
                }),
        ];
    }

    public function getTabs(): array
    {
        return $this->getDynamicTabs('builder.resources.builder.tabs', Item::class);
    }
}
