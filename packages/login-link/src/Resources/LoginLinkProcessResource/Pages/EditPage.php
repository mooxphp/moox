<?php

declare(strict_types=1);

namespace Moox\LoginLink\Resources\LoginLinkProcessResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\LoginLink\Resources\LoginLinkProcessResource;
use Override;

class EditPage extends EditRecord
{
    protected static string $resource = LoginLinkProcessResource::class;

    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
