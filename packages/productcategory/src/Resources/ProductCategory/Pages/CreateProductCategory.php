<?php

declare(strict_types=1);

namespace Moox\ProductCategory\Resources\ProductCategory\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\ProductCategory\Resources\ProductCategoryResource;

class CreateProductCategory extends CreateRecord
{
    protected static string $resource = ProductCategoryResource::class;
}
