<?php

namespace Moox\PressWiki\Resources\WpWikiResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpWikiResource;

class EditWpWiki extends EditRecord
{
    protected static string $resource = WpWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
