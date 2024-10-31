<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Preview\Filament\Resources\BlubResource;

class ListBlubs extends ListRecords
{
    protected static string $resource = BlubResource::class;
}
