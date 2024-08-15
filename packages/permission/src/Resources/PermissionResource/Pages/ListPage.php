<?php

namespace Moox\Permission\Resources\PermissionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Permission\Models\Permission;
use Moox\Permission\Resources\PermissionResource;
use Moox\Permission\Resources\PermissionResource\Widgets\PermissionWidgets;

class ListPage extends ListRecords
{
    public static string $resource = PermissionResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            PermissionWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('permission::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Permission {
                    return $model::create($data);
                }),
        ];
    }
}
