<?php

declare(strict_types=1);

namespace Moox\Organization\Resources\LegalForm\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Organization\Resources\LegalFormResource;

class ListLegalForms extends ListRecords
{
    protected static string $resource = LegalFormResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
