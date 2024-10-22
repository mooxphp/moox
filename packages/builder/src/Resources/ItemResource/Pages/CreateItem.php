<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\HandlesDynamicTaxonomies;

class CreateItem extends CreateRecord
{
    use HandlesDynamicTaxonomies;

    protected static string $resource = ItemResource::class;

    protected function getFormActions(): array
    {
        return [];
    }
}
