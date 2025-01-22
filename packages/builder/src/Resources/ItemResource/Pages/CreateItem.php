<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\ItemResource\Pages;

use Override;
use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Resources\ItemResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInPages;

class CreateItem extends CreateRecord
{
    use TaxonomyInPages;

    protected static string $resource = ItemResource::class;

    #[Override]
    protected function getFormActions(): array
    {
        return [];
    }
}
