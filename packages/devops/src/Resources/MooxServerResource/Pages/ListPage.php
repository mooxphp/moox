<?php

namespace Moox\Devops\Resources\MooxServerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\Devops\Resources\MooxServerResource;
use Moox\Devops\Resources\MooxServerResource\Widgets\MooxServerWidgets;

class ListPage extends ListRecords
{
    public static string $resource = MooxServerResource::class;

    public function getActions(): array
    {
        return [];
    }

    public function getHeaderWidgets(): array
    {
        return [
            // MooxServerWidgets::class,
        ];
    }

    public function getTitle(): string
    {
        return __('devops::translations.title');
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
