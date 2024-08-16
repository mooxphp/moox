<?php

namespace Moox\Sync\Resources\PlatformResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use Moox\Sync\Resources\PlatformResource;

class EditPlatform extends EditRecord
{
    protected static string $resource = PlatformResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    public function generateToken(): void
    {
        $this->data['api_token'] = Str::random(80);
    }
}
