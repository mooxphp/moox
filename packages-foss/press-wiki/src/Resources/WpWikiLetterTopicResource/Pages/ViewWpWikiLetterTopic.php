<?php

namespace Moox\PressWiki\Resources\WpWikiLetterTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpWikiLetterTopicResource;

class ViewWpWikiLetterTopic extends ViewRecord
{
    protected static string $resource = WpWikiLetterTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
