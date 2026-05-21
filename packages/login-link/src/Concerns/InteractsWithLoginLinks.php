<?php

namespace Moox\LoginLink\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Moox\LoginLink\Services\LoginLinkRateLimiter;
use Moox\LoginLink\Services\LoginLinkService;

trait InteractsWithLoginLinks
{
    public function bootInteractsWithLoginLinks(): void
    {
        if ($message = session()->pull('login_link_error')) {
            Notification::make()
                ->danger()
                ->title($message)
                ->send();
        }
    }

    protected function loginLinkIsEnabled(): bool
    {
        return (bool) config('login-link.passwordless.enabled', false);
    }

    public function sendMagicLink(): void
    {
        if (! $this->loginLinkIsEnabled()) {
            return;
        }

        $email = $this->resolveLoginLinkEmailFromForm();

        if ($email === null) {
            Notification::make()
                ->danger()
                ->title(__('login-link::translations.login_enter_email_title'))
                ->send();

            return;
        }

        $rateLimiter = app(LoginLinkRateLimiter::class);

        if ($rateLimiter->tooManySendAttempts($email)) {
            Notification::make()
                ->danger()
                ->title(__('login-link::translations.login_throttled_title'))
                ->send();

            return;
        }

        $rateLimiter->hitSendAttempt($email);

        $result = app(LoginLinkService::class)->sendForEmail(
            filament()->getCurrentPanel()->getId(),
            filament()->getCurrentPanel()->getAuthGuard(),
            $email,
            request(),
        );

        if ($result === 'denied') {
            Notification::make()
                ->danger()
                ->title(__('login-link::translations.login_link_not_sent_title'))
                ->body(__('login-link::translations.login_link_not_sent_body'))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('login-link::translations.login_link_sent_title'))
            ->send();
    }

    protected function configureLoginFormWithMagicLink(Component $component): Component
    {
        if (! $this->loginLinkIsEnabled() || ! $component instanceof TextInput) {
            return $component;
        }

        return $component->hintAction(
            Action::make('sendMagicLink')
                ->label(__('login-link::translations.login_send_link_action'))
                ->icon('heroicon-o-key')
                ->color('gray')
                ->alpineClickHandler('$wire.sendMagicLink()')
                ->extraAttributes(['type' => 'button']),
        );
    }

    protected function resolveLoginLinkEmailFromForm(): ?string
    {
        $login = trim((string) ($this->data['login'] ?? $this->data['email'] ?? ''));

        if ($login === '') {
            return null;
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            return mb_strtolower($login);
        }

        return app(LoginLinkService::class)->resolveEmailForIdentifier(
            filament()->getCurrentPanel()->getAuthGuard(),
            $login,
        );
    }
}
