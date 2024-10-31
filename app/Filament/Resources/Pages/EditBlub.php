<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\BlubResource;
use Filament\Resources\Pages\EditRecord;

class EditBlub extends EditRecord
{
    protected static string $resource = BlubResource::class;
}
