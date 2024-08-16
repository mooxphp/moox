<?php

namespace Moox\RedisModel\Resources\RedisModelResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\RedisModel\Models\RedisModel;
use Moox\RedisModel\Resources\RedisModelResource;
use Moox\RedisModel\Resources\RedisModelResource\Widgets\RedisModelWidgets;

class ListPage extends ListRecords
{
    public static string $resource = RedisModelResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            RedisModelWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('redis-model::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): RedisModel {
                    return $model::create($data);
                }),
        ];
    }
}
