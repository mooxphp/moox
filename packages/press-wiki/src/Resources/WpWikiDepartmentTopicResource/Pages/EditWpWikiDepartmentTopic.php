<?php

namespace Moox\PressWiki\Resources\WpWikiDepartmentTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpWikiDepartmentTopicResource;

class EditWpWikiDepartmentTopic extends EditRecord
{
    protected static string $resource = WpWikiDepartmentTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
