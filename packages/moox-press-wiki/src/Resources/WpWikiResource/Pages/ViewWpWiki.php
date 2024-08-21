<?php

namespace Moox\MooxPressWiki\Resources\MooxPressWikiResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpWikiResource;

class ViewWpWiki extends ViewRecord
{
    protected static string $resource = WpWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
