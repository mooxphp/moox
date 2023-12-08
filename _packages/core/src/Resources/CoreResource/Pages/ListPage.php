<?php

namespace Moox\Core\Resources\CoreResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Core\Models\Core;
use Moox\Core\Resources\CoreResource;
use Moox\Core\Resources\CoreResource\Widgets\CoreWidgets;

class ListPage extends ListRecords
{
    public static string $resource = CoreResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            CoreWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('core::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Core {
                    return $model::create($data);
                }),
        ];
    }
}
