<?php

declare(strict_types=1);

namespace Moox\Press\Resources\WpSiteMetaResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Press\Resources\WpSiteMetaResource;

class CreateWpSiteMeta extends CreateRecord
{
    protected static string $resource = WpSiteMetaResource::class;
}
