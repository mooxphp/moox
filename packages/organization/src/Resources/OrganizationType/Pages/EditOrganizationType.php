<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\OrganizationType\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Organization\Resources\OrganizationTypeResource;

class EditOrganizationType extends EditRecord
{
    protected static string $resource = OrganizationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
