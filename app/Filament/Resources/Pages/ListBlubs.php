<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\BlubResource;

class ListBlubs extends ListRecords
{
    protected static string $resource = BlubResource::class;
}
