<?php

namespace Moox\Press\Resources\WpPageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Press\Resources\WpPageResource;

class CreateWpPost extends CreateRecord
{
    protected static string $resource = WpPageResource::class;
}
