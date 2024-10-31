<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources\Pages;

use App\Preview\Filament\Resources\BlubResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlub extends CreateRecord
{
    protected static string $resource = BlubResource::class;
}
