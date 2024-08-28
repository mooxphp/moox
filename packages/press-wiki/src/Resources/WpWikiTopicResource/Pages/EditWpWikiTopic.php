<?php

namespace Moox\PressWiki\Resources\WpWikiTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpWikiTopicResource;

class EditWpWikiTopic extends EditRecord
{
    protected static string $resource = WpWikiTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
