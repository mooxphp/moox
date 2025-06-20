<?php

namespace Moox\PressWiki\Resources\WpWikiLocationTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpWikiLocationTopicResource;

class ViewWpWikiLocationTopic extends ViewRecord
{
    protected static string $resource = WpWikiLocationTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
