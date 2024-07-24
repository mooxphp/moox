<?php

namespace Moox\Press\Resources\WpWikiResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpWikiResource;

class EditWpWiki extends EditRecord
{
    protected static string $resource = WpWikiResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
