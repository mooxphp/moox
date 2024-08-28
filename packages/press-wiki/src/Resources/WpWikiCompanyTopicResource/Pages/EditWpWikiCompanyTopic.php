<?php

namespace Moox\PressWiki\Resources\WpWikiCompanyTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpWikiCompanyTopicResource;

class EditWpWikiCompanyTopic extends EditRecord
{
    protected static string $resource = WpWikiCompanyTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
