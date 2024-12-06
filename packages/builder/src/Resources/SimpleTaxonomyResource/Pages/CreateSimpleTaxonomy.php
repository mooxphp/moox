<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\SimpleTaxonomyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Resources\SimpleTaxonomyResource;
use Moox\Core\Traits\Taxonomy\TaxonomyInPages;

class CreateSimpleTaxonomy extends CreateRecord
{
    use TaxonomyInPages;

    protected static string $resource = SimpleTaxonomyResource::class;

    protected function getFormActions(): array
    {
        return [];
    }
}
