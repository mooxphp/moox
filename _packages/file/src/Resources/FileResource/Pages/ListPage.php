<?php

namespace Moox\File\Resources\FileResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\File\Models\File;
use Moox\File\Resources\FileResource;
use Moox\File\Resources\FileResource\Widgets\FileWidgets;

class ListPage extends ListRecords
{
    public static string $resource = FileResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            FileWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('file::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): File {
                    return $model::create($data);
                }),
        ];
    }
}
