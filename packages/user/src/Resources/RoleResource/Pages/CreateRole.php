<?php declare(strict_types=1);

namespace Moox\User\Resources\RoleResource\Pages;

use Moox\User\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    public static function getResource(): string
    {
        return  RoleResource::class;
    }

    public function afterCreate(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['guard_name'] = config('user.guard_name');

        return $data;
    }
}
