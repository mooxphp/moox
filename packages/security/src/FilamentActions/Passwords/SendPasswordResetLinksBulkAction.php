<?php

declare(strict_types=1);

namespace Moox\Security\FilamentActions\Passwords;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
use Filament\Actions\BulkAction;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
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
            ->label(__('security::translations.send_password_reset_links'))
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

                $authUser = auth()->user();
                $sent = false;

                $broker = Password::broker(Filament::getAuthPasswordBroker());
                $panelId = Filament::getCurrentOrDefaultPanel()->getId();

                /** @var array<string, true> $processedEmails */
                $processedEmails = [];

                foreach ($records as $record) {
                    if ($authUser instanceof Model && $record->is($authUser)) {
                        continue;
                    }

                    if (Gate::getPolicyFor($record) !== null && ! Gate::allows('update', $record)) {
                        continue;
                    }

                    if (! $record instanceof CanResetPassword) {
                        $recordClass = $record::class;
                        throw new Exception(sprintf('Model [%s] must implement [Illuminate\Contracts\Auth\CanResetPassword] interface.', $recordClass));
                    }

                    $email = $record->getEmailForPasswordReset();

                    if ($email === '' || isset($processedEmails[$email])) {
                        continue;
                    }

                    $processedEmails[$email] = true;

                    $token = $broker->createToken($record);

                    $record->notify(PasswordResetNotification::forToken($token, $panelId));
                    $sent = true;
                }

                if (! $sent) {
                    return;
                }

                Notification::make()
                    ->title(__('security::translations.password_reset_links_sent'))
                    ->success()
                    ->send();
            });
    }

    public function rateLimit(int|\Closure|null $maxAttempts): static
    {
        return parent::rateLimit($maxAttempts);
    }
}
