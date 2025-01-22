<?php

namespace Moox\Permission\Resources\PermissionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Permission\Models\Permission;
use Moox\Permission\Resources\PermissionResource;
use Moox\Permission\Resources\PermissionResource\Widgets\PermissionWidgets;
use Override;

class ListPage extends ListRecords
{
    public static string $resource = PermissionResource::class;

    protected function getActions(): array
    {
        return [];
    }

    #[Override]
    protected function getHeaderWidgets(): array
    {
        return [
            PermissionWidgets::class,
        ];
    }

    #[Override]
    public function getTitle(): string
    {
        return __('permission::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(fn (array $data, string $model): Permission => $model::create($data)),
        ];
    }
}
