<?php

declare(strict_types=1);

namespace App\Builder\Resources\SimpleTaxonomyResource\Pages;

use App\Builder\Resources\SimpleTaxonomyResource;
use Filament\Resources\Pages\EditRecord;

class EditSimpleTaxonomy extends EditRecord
{
    protected static string $resource = SimpleTaxonomyResource::class;
}
