<?php

declare(strict_types=1);

namespace Moox\Connect\Filament\Resources\ApiConnectionResource\Pages;

use Moox\Connect\Filament\Resources\ApiConnectionResource;
use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;

class EditApiConnection extends BaseEditRecord
{
    protected static string $resource = ApiConnectionResource::class;
}
