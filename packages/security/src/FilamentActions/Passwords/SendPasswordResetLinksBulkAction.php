<?php

namespace Moox\Security\FilamentActions\Passwords;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Collection;
use Moox\Security\Notifications\Passwords\PasswordResetNotification;
use Override;

class SendPasswordResetLinksBulkAction extends BulkAction
{
    use CanCustomizeProcess;
    use WithRateLimiting;

    public static function getDefaultName(): ?string
    {
        return 'sendPasswordResetLinks';
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('security::translations.Send Password Reset Links'))
            ->requiresConfirmation()
            ->action(function (Collection $records): void {
                try {
                    $this->rateLimit(2);
                } catch (TooManyRequestsException $tooManyRequestsException) {
                    Notification::make()
                        ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.title', [
                            'seconds' => $tooManyRequestsException->secondsUntilAvailable,
                            'minutes' => ceil($tooManyRequestsException->secondsUntilAvailable / 60),
                        ]))
                        ->body(array_key_exists('body', __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.body', [
                            'seconds' => $tooManyRequestsException->secondsUntilAvailable,
                            'minutes' => ceil($tooManyRequestsException->secondsUntilAvailable / 60),
                        ]) : null)
                        ->danger()
                        ->send();

                    return;
                }

                foreach ($records as $record) {
                    if (! $record instanceof CanResetPassword) {
                        $recordClass = $record::class;
                        throw new Exception(sprintf('Model [%s] must implement [Illuminate\Contracts\Auth\CanResetPassword] interface.', $recordClass));
                    }

                    $user = $record;

                    $token = app('auth.password.broker')->createToken($user);

                    if (! method_exists($user, 'notify')) {
                        $userClass = $user::class;

                        throw new Exception(sprintf('Model [%s] does not have a [notify()] method.', $userClass));
                    }

                    $notification = new PasswordResetNotification($token);

                    $user->notify($notification);
                }

                Notification::make()
                    ->title(__('security::translations.Password reset links sent'))
                    ->success()
                    ->send();
            });
    }
}
