<?php

declare(strict_types=1);

namespace Moox\Communication\Resources\CommunicationRule\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Communication\Resources\CommunicationRuleResource;

class ListCommunicationRules extends ListRecords
{
    protected static string $resource = CommunicationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
