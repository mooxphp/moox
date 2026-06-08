<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticVatCategoryResource\Pages;

use Moox\Core\Entities\Items\Record\Pages\BaseEditRecord;
use Moox\Data\Filament\Resources\StaticVatCategoryResource;

class EditStaticVatCategory extends BaseEditRecord
{
    protected static string $resource = StaticVatCategoryResource::class;
}
