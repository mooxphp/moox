<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierRule\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Contact\Resources\ContactIdentifierRuleResource;

class ListContactIdentifierRules extends ListRecords
{
    protected static string $resource = ContactIdentifierRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
