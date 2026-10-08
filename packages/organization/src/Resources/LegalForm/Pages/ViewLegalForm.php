<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\LegalForm\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Organization\Resources\LegalFormResource;

class ViewLegalForm extends ViewRecord
{
    protected static string $resource = LegalFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
