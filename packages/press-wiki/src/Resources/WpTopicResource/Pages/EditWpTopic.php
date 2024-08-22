<?php

namespace Moox\PressWiki\Resources\WpTopicResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\PressWiki\Resources\WpTopicResource;

class EditWpTopic extends EditRecord
{
    protected static string $resource = WpTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
