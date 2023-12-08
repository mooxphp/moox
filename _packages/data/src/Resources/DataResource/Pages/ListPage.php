<?php

namespace Moox\Data\Resources\DataResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Data\Models\Data;
use Moox\Data\Resources\DataResource;
use Moox\Data\Resources\DataResource\Widgets\DataWidgets;

class ListPage extends ListRecords
{
    public static string $resource = DataResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            DataWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('data::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Data {
                    return $model::create($data);
                }),
        ];
    }
}
