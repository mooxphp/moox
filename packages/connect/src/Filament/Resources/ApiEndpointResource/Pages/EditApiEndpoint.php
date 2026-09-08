<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiEndpointResource\Pages;

use Moox\Connect\Filament\Resources\ApiEndpointResource;
use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;

class EditApiEndpoint extends BaseEditRecord
{
    protected static string $resource = ApiEndpointResource::class;
}
