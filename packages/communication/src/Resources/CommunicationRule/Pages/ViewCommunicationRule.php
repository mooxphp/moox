<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationRule\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Communication\Resources\CommunicationRuleResource;

class ViewCommunicationRule extends ViewRecord
{
    protected static string $resource = CommunicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
