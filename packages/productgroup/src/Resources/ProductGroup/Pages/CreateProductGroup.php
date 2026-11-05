<?php

declare(strict_types=1);

namespace Moox\ProductGroup\Resources\ProductGroup\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\ProductGroup\Resources\ProductGroupResource;

class CreateProductGroup extends CreateRecord
{
    protected static string $resource = ProductGroupResource::class;
}
