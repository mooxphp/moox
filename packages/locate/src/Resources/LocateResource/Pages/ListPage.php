<?php

namespace Moox\Locate\Resources\LocateResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Locate\Models\Locate;
use Moox\Locate\Resources\LocateResource;
use Moox\Locate\Resources\LocateResource\Widgets\LocateWidgets;

class ListPage extends ListRecords
{
    public static string $resource = LocateResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            LocateWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('locate::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Locate {
                    return $model::create($data);
                }),
        ];
    }
}
