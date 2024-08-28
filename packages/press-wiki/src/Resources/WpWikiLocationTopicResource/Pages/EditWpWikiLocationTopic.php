<?php

namespace Moox\PressWiki\Resources\WpWikiLocationTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpWikiLocationTopicResource;

class EditWpWikiLocationTopic extends EditRecord
{
    protected static string $resource = WpWikiLocationTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
