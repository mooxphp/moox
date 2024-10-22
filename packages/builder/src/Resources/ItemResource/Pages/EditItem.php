<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\HandlesDynamicTaxonomies;

class EditItem extends EditRecord
{
    use HandlesDynamicTaxonomies;

    protected static string $resource = ItemResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
