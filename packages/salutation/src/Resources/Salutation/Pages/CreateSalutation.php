<?php

declare(strict_types=1);

namespace Moox\Salutation\Resources\Salutation\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Salutation\Resources\SalutationResource;

class CreateSalutation extends CreateRecord
{
    protected static string $resource = SalutationResource::class;
}
