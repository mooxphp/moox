<?php

declare(strict_types=1);

namespace Moox\Data\Filament\Resources\StaticVatCategoryResource\Pages;

use Moox\Core\Entities\Items\Static\Pages\BaseEditStaticRecord;
use Moox\Data\Filament\Resources\StaticVatCategoryResource;

class EditStaticVatCategory extends BaseEditStaticRecord
{
    protected static string $resource = StaticVatCategoryResource::class;
}
