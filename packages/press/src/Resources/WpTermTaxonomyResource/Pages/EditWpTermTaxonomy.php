<?php

namespace Moox\Press\Resources\WpTermTaxonomyResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpTermTaxonomyResource;

class EditWpTermTaxonomy extends EditRecord
{
    protected static string $resource = WpTermTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
