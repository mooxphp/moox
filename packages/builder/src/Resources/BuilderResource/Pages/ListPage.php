<?php

namespace Moox\Builder\Resources\BuilderResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Builder\Models\Item;
use Moox\Builder\Resources\BuilderResource;
use Moox\Builder\Resources\BuilderResource\Widgets\BuilderWidgets;

class ListPage extends ListRecords
{
    public static string $resource = BuilderResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            BuilderWidgets::class,
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
}
