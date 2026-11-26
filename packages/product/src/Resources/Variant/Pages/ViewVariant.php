<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Variant\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Product\Resources\Variant\Pages\Concerns\FillsInheritedVariantData;
use Moox\Product\Resources\VariantResource;

class ViewVariant extends ViewRecord
{
    use FillsInheritedVariantData;

    protected static string $resource = VariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
