<?php

declare(strict_types=1);

namespace Moox\Security\FilamentActions\Passwords;

use Exception;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Moox\Security\Notifications\Passwords\PasswordResetNotification;
use Override;

interface CanNotifyForPasswordReset
{
    public function notify(\Illuminate\Notifications\Notification $notification): void;
}

class SendPasswordResetLinkAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'sendPasswordResetLink';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('security::translations.send_password_reset_link'))
            ->requiresConfirmation()
            ->rateLimit(2)
            ->action(function (mixed $record): void {
                if (! $record instanceof CanResetPassword) {
                    $recordClass = is_object($record) ? $record::class : get_debug_type($record);

                    throw new Exception(sprintf('Model [%s] must implement [Illuminate\\Contracts\\Auth\\CanResetPassword] interface.', $recordClass));
                }

                if ($record instanceof Model
                    && Gate::getPolicyFor($record) !== null
                    && ! Gate::allows('update', $record)) {
                    Notification::make()
                        ->title(__('security::translations.password_reset_link_not_sent'))
                        ->danger()
                        ->send();

                    return;
                }

                $email = $record->getEmailForPasswordReset();

                if ($email === '') {
                    Notification::make()
                        ->title(__('security::translations.password_reset_link_not_sent'))
                        ->body(__('security::translations.password_reset_missing_email'))
                        ->danger()
                        ->send();

                    return;
                }

                /** @var \Illuminate\Auth\Passwords\PasswordBroker $broker */
                $broker = Password::broker(Filament::getAuthPasswordBroker());

                $token = $broker->createToken($record);

                /** @var CanResetPassword&CanNotifyForPasswordReset $record */
                $record->notify(PasswordResetNotification::forToken(
                    $token,
                    Filament::getCurrentOrDefaultPanel()->getId(),
                ));

                Notification::make()
                    ->title(__('security::translations.password_reset_link_sent'))
                    ->success()
                    ->send();
            });
    }
}
