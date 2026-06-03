<?php

namespace Moox\Security\Services;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Password;
use Moox\Security\Notifications\Passwords\PasswordResetNotification;
use Override;

/**
 * @property Schema $form
 */
class RequestPasswordReset extends SimplePage
{
    use InteractsWithFormActions;
    use RestrictsFileUploadsToSchemaComponents;
    use WithRateLimiting;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $tooManyRequestsException) {
            Notification::make()
                ->title(__('filament-panels::auth/pages/password-reset/request-password-reset.notifications.throttled.title', [
                    'seconds' => $tooManyRequestsException->secondsUntilAvailable,
                    'minutes' => ceil($tooManyRequestsException->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::auth/pages/password-reset/request-password-reset.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/password-reset/request-password-reset.notifications.throttled.body', [
                    'seconds' => $tooManyRequestsException->secondsUntilAvailable,
                    'minutes' => ceil($tooManyRequestsException->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $data,
            function (CanResetPassword $user, string $token): void {
                if (! method_exists($user, 'notify')) {
                    return;
                }

                $user->notify(PasswordResetNotification::forToken($token));
            },
        );

        Notification::make()
            ->title(__($status === Password::RESET_LINK_SENT ? $status : Password::RESET_LINK_SENT))
            ->success()
            ->send();

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
            ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/password-reset/request-password-reset.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus();
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('filament-panels::auth/pages/password-reset/request-password-reset.actions.login.label'))
            ->icon(match (__('filament-panels::layout.direction')) {
                'rtl' => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login.rtl') ?? 'heroicon-m-arrow-right',
                default => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login') ?? 'heroicon-m-arrow-left',
            })
            ->url(filament()->getLoginUrl());
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::auth/pages/password-reset/request-password-reset.title');
    }

    #[Override]
    public function getHeading(): string|Htmlable
    {
        return __('filament-panels::auth/pages/password-reset/request-password-reset.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction(),
        ];
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label(__('filament-panels::auth/pages/password-reset/request-password-reset.form.actions.request.label'))
            ->submit('request');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_BEFORE),
                $this->getFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_REQUEST_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('request')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }
}
