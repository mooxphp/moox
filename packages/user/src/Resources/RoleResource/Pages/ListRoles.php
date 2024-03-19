<?php declare(strict_types=1);

namespace Moox\User\Resources\RoleResource\Pages;

use Moox\User\Resources\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRoles extends ListRecords
{

    public static function getResource(): string
    {
        return  RoleResource::class;
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
