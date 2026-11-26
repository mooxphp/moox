<?php

declare(strict_types=1);

namespace Moox\Product\Resources\Variant\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Product\Resources\Variant\Pages\Concerns\FillsInheritedVariantData;
use Moox\Product\Resources\VariantResource;

class EditVariant extends EditRecord
{
    use FillsInheritedVariantData;

    protected static string $resource = VariantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
