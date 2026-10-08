<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\LegalForm\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Organization\Resources\LegalFormResource;

class EditLegalForm extends EditRecord
{
    protected static string $resource = LegalFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
