<?php

namespace Moox\User\Resources\UserResource\Pages;

use Moox\User\Models\User;
use Filament\Facades\Filament;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Hash;
use Moox\User\Resources\UserResource;
use Filament\Notifications\Notification;
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
            if ($this->record instanceof User) {
                $this->record->password = Hash::make($data['new_password']);
            }
        }

        return $data;
    }

    public function afterSave()
    {
        session()->forget('password_hash_'.Filament::getCurrentPanel()->getAuthGuard());
        $this->refreshFormData(['new_password', 'current_password', 'new_password_confirmation']);

        return redirect('moox/users');
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
        ->success()
        ->title('User updated')
        ->body('The user has been saved successfully.');
    }
}
