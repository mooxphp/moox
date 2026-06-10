<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteMetaResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Moox\Press\Resources\WpSiteMetaResource;

class ViewWpSiteMeta extends ViewRecord
{
    protected static string $resource = WpSiteMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }
}
