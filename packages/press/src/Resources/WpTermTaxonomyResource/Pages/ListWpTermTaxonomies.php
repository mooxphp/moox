<?php

namespace Moox\Press\Resources\WpTermTaxonomyResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Press\Resources\WpTermTaxonomyResource;

class ListWpTermTaxonomies extends ListRecords
{
    protected static string $resource = WpTermTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
