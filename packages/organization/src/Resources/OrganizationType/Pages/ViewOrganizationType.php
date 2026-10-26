<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\OrganizationType\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Organization\Resources\OrganizationTypeResource;

class ViewOrganizationType extends ViewRecord
{
    protected static string $resource = OrganizationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
