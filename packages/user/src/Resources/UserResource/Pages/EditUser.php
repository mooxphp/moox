<?php

namespace Moox\User\Resources\UserResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Moox\User\Resources\UserResource;
use Override;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected bool $passwordWasChanged = false;

    protected function getHeaderActions(): array
    {
        return array_values(array_filter([
            UserResource::shouldShowSendPasswordResetLinkAction()
                ? \Moox\Security\FilamentActions\Passwords\SendPasswordResetLinkAction::make()
                    ->visible(fn (): bool => ! UserResource::canManagePassword($this->getRecord())
                        && UserResource::canSendPasswordResetTo($this->getRecord()))
                : null,
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ]));
    }

    #[Override]
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (UserResource::canManagePassword($this->getRecord()) && filled($data['new_password'] ?? null)) {
            $data['password'] = Hash::make((string) $data['new_password']);
            $this->passwordWasChanged = true;
        }

        unset($data['current_password'], $data['new_password'], $data['new_password_confirmation']);

        return $data;
    }

    public function afterSave(): void
    {
        if (UserResource::canManagePassword($this->getRecord()) && $this->passwordWasChanged) {
            session()->forget('password_hash_'.Filament::getCurrentOrDefaultPanel()->getAuthGuard());
        }

        $this->refreshFormData(['new_password', 'current_password', 'new_password_confirmation']);

        $this->redirect(UserResource::getUrl('index'));
    }

    #[Override]
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('core::user.user_updated'))
            ->body(__('core::user.user_updated_message'));
    }
}
