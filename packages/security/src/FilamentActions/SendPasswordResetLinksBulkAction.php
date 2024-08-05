<?php

namespace Moox\Security\FilamentActions;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Moox\Security\Notifications\PasswordResetNotification;

class SendPasswordResetLinksBulkAction extends BulkAction
{
    use CanCustomizeProcess, WithRateLimiting;

    public static function getDefaultName(): ?string
    {
        return 'sendPasswordResetLinks';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('security::translations.Send Password Reset Links'))
            ->requiresConfirmation()
            ->action(function (Collection $records) {
                try {
                    $this->rateLimit(2);
                } catch (TooManyRequestsException $exception) {
                    Notification::make()
                        ->title(__('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.title', [
                            'seconds' => $exception->secondsUntilAvailable,
                            'minutes' => ceil($exception->secondsUntilAvailable / 60),
                        ]))
                        ->body(array_key_exists('body', __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/password-reset/request-password-reset.notifications.throttled.body', [
                            'seconds' => $exception->secondsUntilAvailable,
                            'minutes' => ceil($exception->secondsUntilAvailable / 60),
                        ]) : null)
                        ->danger()
                        ->send();

                    return;
                }

                foreach ($records as $record) {
                    $user = $record;

                    $token = app('auth.password.broker')->createToken($user);
                    if (! method_exists($user, 'notify')) {
                        $userClass = $user::class;

                        throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
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
