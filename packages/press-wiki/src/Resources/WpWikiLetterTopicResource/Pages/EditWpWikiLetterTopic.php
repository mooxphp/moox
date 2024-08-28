<?php

namespace Moox\PressWiki\Resources\WpWikiLetterTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpWikiLetterTopicResource;

class EditWpWikiLetterTopic extends EditRecord
{
    protected static string $resource = WpWikiLetterTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
