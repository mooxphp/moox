<?php

declare(strict_types=1);

namespace Moox\Builder\Resources\NestedTaxonomyResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Builder\Resources\NestedTaxonomyResource;
use Moox\Core\Traits\TaxonomyInPages;

class CreateNestedTaxonomy extends CreateRecord
{
    use TaxonomyInPages;

    protected static string $resource = NestedTaxonomyResource::class;

    protected function getFormActions(): array
    {
        return [];
    }
}
