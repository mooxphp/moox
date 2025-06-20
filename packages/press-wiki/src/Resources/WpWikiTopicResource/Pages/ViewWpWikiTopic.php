<?php

namespace Moox\PressWiki\Resources\WpWikiTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpWikiTopicResource;

class ViewWpWikiTopic extends ViewRecord
{
    protected static string $resource = WpWikiTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
