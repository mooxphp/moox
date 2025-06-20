<?php

namespace Moox\Devops\Resources\MooxProjectResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Devops\Resources\MooxProjectResource;
use Moox\Devops\Resources\MooxProjectResource\Widgets\MooxProjectWidgets;

class ListPage extends ListRecords
{
    public static string $resource = MooxProjectResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            // MooxProjectWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('devops::translations.forge_projects');
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
