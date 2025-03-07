<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Connect\Resources\ApiLogResource;

final class ListApiLogs extends ListRecords
{
    protected static string $resource = ApiLogResource::class;
}
