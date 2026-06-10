<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticUnitResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\Data\Filament\Resources\StaticUnitResource;

class CreateStaticUnit extends BaseCreateRecord
{
    protected static string $resource = StaticUnitResource::class;
}
