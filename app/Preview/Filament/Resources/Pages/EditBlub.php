<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources\Pages;

use Filament\Resources\Pages\EditRecord;
use App\Preview\Filament\Resources\BlubResource;

class EditBlub extends EditRecord
{
    protected static string $resource = BlubResource::class;
}
