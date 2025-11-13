<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticLocaleResource;

class CreateStaticLocale extends BaseCreateRecord
{
    protected static string $resource = StaticLocaleResource::class;
}
