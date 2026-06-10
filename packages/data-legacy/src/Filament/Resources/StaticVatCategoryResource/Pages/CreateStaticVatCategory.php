<?php

declare(strict_types=1);

namespace Moox\DataLegacy\Filament\Resources\StaticVatCategoryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseCreateRecord;
use Moox\DataLegacy\Filament\Resources\StaticVatCategoryResource;

class CreateStaticVatCategory extends BaseCreateRecord
{
    protected static string $resource = StaticVatCategoryResource::class;
}
