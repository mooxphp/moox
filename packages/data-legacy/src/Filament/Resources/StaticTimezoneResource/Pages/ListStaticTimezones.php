<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticTimezoneResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseListRecords;
use Moox\Core\Traits\Base\BaseInListPage;
use Moox\DataLegacy\Filament\Resources\StaticTimezoneResource;

class ListStaticTimezones extends BaseListRecords
{
    use BaseInListPage;

    protected static string $resource = StaticTimezoneResource::class;
}
