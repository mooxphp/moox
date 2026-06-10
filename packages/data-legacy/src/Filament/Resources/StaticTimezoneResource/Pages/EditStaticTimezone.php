<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticTimezoneResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\DataLegacy\Filament\Resources\StaticTimezoneResource;

class EditStaticTimezone extends BaseEditRecord
{
    protected static string $resource = StaticTimezoneResource::class;
}
