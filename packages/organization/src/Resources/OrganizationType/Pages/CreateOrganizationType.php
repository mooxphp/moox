<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\OrganizationType\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Organization\Resources\OrganizationTypeResource;

class CreateOrganizationType extends CreateRecord
{
    protected static string $resource = OrganizationTypeResource::class;
}
