<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources\Pages;

use Filament\Resources\Pages\ViewRecord;
use App\Preview\Filament\Resources\BlubResource;

class ViewBlub extends ViewRecord
{
    protected static string $resource = BlubResource::class;
}
