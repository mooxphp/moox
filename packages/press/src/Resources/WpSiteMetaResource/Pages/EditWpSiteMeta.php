<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Moox\Press\Resources\WpSiteMetaResource;

class EditWpSiteMeta extends EditRecord
{
    protected static string $resource = WpSiteMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
