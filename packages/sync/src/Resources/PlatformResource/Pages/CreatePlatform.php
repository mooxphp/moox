<?php

namespace Moox\Sync\Resources\PlatformResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Moox\Sync\Resources\PlatformResource;

class CreatePlatform extends CreateRecord
{
    protected static string $resource = PlatformResource::class;

    public function generateToken(): void
    {
        $this->form->fill([
            'api_token' => Str::random(80),
        ]);
    }
}
