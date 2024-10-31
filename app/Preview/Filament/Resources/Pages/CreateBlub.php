<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Preview\Filament\Resources\BlubResource;

class CreateBlub extends CreateRecord
{
    protected static string $resource = BlubResource::class;
}
