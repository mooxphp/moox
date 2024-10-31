<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\BlubResource;

class EditBlub extends EditRecord
{
    protected static string $resource = BlubResource::class;
}
