<?php

declare(strict_types=1);

namespace Moox\BackupServerUi\Resources\SourceResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Moox\BackupServerUi\Resources\SourceResource;
use Moox\Core\Entities\Items\Item\Pages\BaseViewItem;

class ViewSource extends BaseViewItem
{
    protected static string $resource = SourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
