<?php

declare(strict_types=1);

namespace Moox\Connect\Resources\ApiLogResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use Moox\Connect\Resources\ApiLogResource;

final class ViewApiLog extends ViewRecord
{
    protected static string $resource = ApiLogResource::class;
}
