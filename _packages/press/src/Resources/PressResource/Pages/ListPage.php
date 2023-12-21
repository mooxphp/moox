<?php

namespace Moox\Press\Resources\PressResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Models\Press;
use Moox\Press\Resources\PressResource;
use Moox\Press\Resources\PressResource\Widgets\PressWidgets;

class ListPage extends ListRecords
{
    public static string $resource = PressResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            PressWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('press::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Press {
                    return $model::create($data);
                }),
        ];
    }
}
