<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Variant\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Product\Resources\VariantResource;

class CreateVariant extends CreateRecord
{
    protected static string $resource = VariantResource::class;
}
