<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticUnitResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseCreateStaticRecord;
use Moox\Data\Filament\Resources\StaticUnitResource;

class CreateStaticUnit extends BaseCreateStaticRecord
{
    protected static string $resource = StaticUnitResource::class;
}
