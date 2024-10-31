<?php

declare(strict_types=1);

namespace App\Preview\Filament\Resources\Pages;

use App\Preview\Filament\Resources\BlubResource;
use Filament\Resources\Pages\EditRecord;

class EditBlub extends EditRecord
{
    protected static string $resource = BlubResource::class;
}
