<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources\LoginLinkProcessResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Moox\LoginLink\Resources\LoginLinkProcessResource;
use Override;

class ListPage extends ListRecords
{
    protected static string $resource = LoginLinkProcessResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
