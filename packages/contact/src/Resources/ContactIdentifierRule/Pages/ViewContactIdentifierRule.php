<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierRule\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Contact\Resources\ContactIdentifierRuleResource;

class ViewContactIdentifierRule extends ViewRecord
{
    protected static string $resource = ContactIdentifierRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
