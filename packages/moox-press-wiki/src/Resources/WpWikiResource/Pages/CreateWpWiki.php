<?php

namespace Moox\MooxPressWiki\Resources\MooxPressWikiResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Moox\Press\Resources\WpWikiResource;

class CreateWpWiki extends CreateRecord
{
    protected static string $resource = WpWikiResource::class;
}
