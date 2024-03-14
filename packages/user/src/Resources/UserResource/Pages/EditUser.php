<?php

namespace Moox\User\Resources\UserResource\Pages;

use Filament\Facades\Filament;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;
use Moox\User\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;

class EditUser extends EditRecord
{

    
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make(), Impersonate::make()->record($this->getRecord())];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (filled($data['new_password'])) {
            $data = collect($this->form->getState())->only('new_password')->all();
            $this->record->password = Hash::make($data['new_password']);
        }
        
        return $data;
    }
}
