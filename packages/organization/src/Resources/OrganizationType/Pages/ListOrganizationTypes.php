<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\OrganizationType\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Organization\Resources\OrganizationTypeResource;

class ListOrganizationTypes extends ListRecords
{
    protected static string $resource = OrganizationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
