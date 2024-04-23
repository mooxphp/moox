<?php

namespace Moox\UserDevice\Resources\UserDeviceResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\UserDevice\Models\UserDevice;
use Moox\UserDevice\Resources\UserDeviceResource;
use Moox\UserDevice\Resources\UserDeviceResource\Widgets\UserDeviceWidgets;

class ListPage extends ListRecords
{
    public static string $resource = UserDeviceResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            UserDeviceWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('user-device::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): UserDevice {
                    return $model::create($data);
                }),
        ];
    }
}
