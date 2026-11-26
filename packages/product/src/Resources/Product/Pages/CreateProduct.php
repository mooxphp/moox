<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Product\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Product\Resources\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}
