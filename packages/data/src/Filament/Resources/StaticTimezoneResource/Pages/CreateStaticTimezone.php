<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticTimezoneResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticTimezoneResource;

class CreateStaticTimezone extends BaseCreateRecord
{
    protected static string $resource = StaticTimezoneResource::class;
}
