<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationRule\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Communication\Resources\CommunicationRuleResource;

class EditCommunicationRule extends EditRecord
{
    protected static string $resource = CommunicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
