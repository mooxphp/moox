<?php

namespace Moox\PressWiki\Resources\WpWikiDepartmentTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpWikiDepartmentTopicResource;

class ViewWpWikiDepartmentTopic extends ViewRecord
{
    protected static string $resource = WpWikiDepartmentTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
