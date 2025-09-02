<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticLocaleResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Data\Filament\Resources\StaticLocaleResource;

class EditStaticLocale extends BaseEditRecord
{
    protected static string $resource = StaticLocaleResource::class;
}
