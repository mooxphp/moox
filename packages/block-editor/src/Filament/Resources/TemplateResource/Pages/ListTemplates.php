<?php

namespace Moox\BlockEditor\Filament\Resources\TemplateResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Moox\BlockEditor\Filament\Resources\TemplateResource;

class ListTemplates extends ListRecords
{
    protected static string $resource = TemplateResource::class;

    protected static ?string $title = 'Templates';
}
