<?php

namespace Moox\Press\Resources\WpUserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Press\Resources\WpUserResource;

class CreateWpUser extends CreateRecord
{
    protected static string $resource = WpUserResource::class;
}
