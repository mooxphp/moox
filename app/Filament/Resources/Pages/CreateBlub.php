<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BlubResource;

class CreateBlub extends CreateRecord
{
    protected static string $resource = BlubResource::class;
}
