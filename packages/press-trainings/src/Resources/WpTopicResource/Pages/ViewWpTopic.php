<?php

namespace Moox\PressWiki\Resources\WpTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\PressWiki\Resources\WpTopicResource;

class ViewWpTopic extends ViewRecord
{
    protected static string $resource = WpTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
