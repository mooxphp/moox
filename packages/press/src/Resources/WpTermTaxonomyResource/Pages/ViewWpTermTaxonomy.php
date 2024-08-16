<?php

namespace Moox\Press\Resources\WpTermTaxonomyResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpTermTaxonomyResource;

class ViewWpTermTaxonomy extends ViewRecord
{
    protected static string $resource = WpTermTaxonomyResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
