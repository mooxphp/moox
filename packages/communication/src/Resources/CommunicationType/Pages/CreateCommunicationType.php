<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationType\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Communication\Resources\CommunicationTypeResource;

class CreateCommunicationType extends CreateRecord
{
    protected static string $resource = CommunicationTypeResource::class;
}
