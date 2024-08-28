<?php

namespace Moox\PressWiki\Resources\WpWikiCompanyTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpWikiCompanyTopicResource;

class ViewWpWikiCompanyTopic extends ViewRecord
{
    protected static string $resource = WpWikiCompanyTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
