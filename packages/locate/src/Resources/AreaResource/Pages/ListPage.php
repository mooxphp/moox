<?php

namespace Moox\Locate\Resources\AreaResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Locate\Resources\AreaResource;

class ListPage extends ListRecords
{
    public static string $resource = AreaResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            // LocateWidgets::class,
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
                ->using(function (array $data, string $model): static {
                    return $model::create($data);
                }),
        ];
    }
}
