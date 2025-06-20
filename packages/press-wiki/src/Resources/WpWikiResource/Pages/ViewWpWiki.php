<?php

namespace Moox\PressWiki\Resources\WpWikiResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpWikiResource;

class ViewWpWiki extends ViewRecord
{
    protected static string $resource = WpWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
