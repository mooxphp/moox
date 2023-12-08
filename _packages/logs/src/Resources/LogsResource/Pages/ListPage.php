<?php

namespace Moox\Logs\Resources\LogsResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Logs\Models\Logs;
use Moox\Logs\Resources\LogsResource;
use Moox\Logs\Resources\LogsResource\Widgets\LogsWidgets;

class ListPage extends ListRecords
{
    public static string $resource = LogsResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            LogsWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('logs::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Logs {
                    return $model::create($data);
                }),
        ];
    }
}
