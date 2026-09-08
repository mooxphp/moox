<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiLogResource\Pages;

use Moox\Connect\Filament\Resources\ApiLogResource;
use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;

class EditApiLog extends BaseEditRecord
{
    protected static string $resource = ApiLogResource::class;
}
