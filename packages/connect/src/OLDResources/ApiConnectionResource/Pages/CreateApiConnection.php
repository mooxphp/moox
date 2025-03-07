<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiConnectionResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Connect\Resources\ApiConnectionResource;

final class CreateApiConnection extends CreateRecord
{
    protected static string $resource = ApiConnectionResource::class;
}
