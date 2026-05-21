<?php

declare(strict_types=1);

namespace Moox\Scopes\Entities\Scopes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\Scopes\Entities\Scopes\ScopeResource;

class ListScopes extends ListRecords
{
    protected static string $resource = ScopeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
