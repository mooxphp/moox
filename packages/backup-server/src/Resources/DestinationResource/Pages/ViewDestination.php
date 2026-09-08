<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\DestinationResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Moox\BackupServerUi\Resources\DestinationResource;
use Moox\Core\Entities\Items\Item\Pages\BaseViewItem;

class ViewDestination extends BaseViewItem
{
    protected static string $resource = DestinationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
