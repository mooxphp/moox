<?php

declare(strict_types=1);

namespace Moox\User\Resources\PermissionResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\User\Resources\PermissionResource;

class ListPermissions extends ListRecords
{
    public static function getResource(): string
    {
        return PermissionResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
