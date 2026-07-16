<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticUnitResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseEditStaticRecord;
use Moox\Data\Filament\Resources\StaticUnitResource;

class EditStaticUnit extends BaseEditStaticRecord
{
    protected static string $resource = StaticUnitResource::class;
}
