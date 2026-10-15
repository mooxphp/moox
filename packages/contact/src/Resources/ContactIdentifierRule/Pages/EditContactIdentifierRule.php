<?php

declare(strict_types=1);

namespace Moox\Contact\Resources\ContactIdentifierRule\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Contact\Resources\ContactIdentifierRuleResource;

class EditContactIdentifierRule extends EditRecord
{
    protected static string $resource = ContactIdentifierRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
