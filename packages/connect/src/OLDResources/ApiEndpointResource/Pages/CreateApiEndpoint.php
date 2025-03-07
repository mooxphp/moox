<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiEndpointResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Connect\Resources\ApiEndpointResource;

final class CreateApiEndpoint extends CreateRecord
{
    protected static string $resource = ApiEndpointResource::class;
}
