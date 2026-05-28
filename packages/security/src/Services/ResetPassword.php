<?php

declare(strict_types=1);

namespace Moox\Security\Services;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
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
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Locked;
use Moox\Security\Support\ResetsUserPassword;
use Moox\Security\Support\ResolvesPasswordResetRules;
use Override;

/**
 * @property Schema $form
 */
class ResetPassword extends SimplePage
{
    use InteractsWithFormActions;
    use RestrictsFileUploadsToSchemaComponents;
    use ResetsUserPassword;
    use ResolvesPasswordResetRules;
    use WithRateLimiting;

    #[Locked]
    public ?string $email = null;

    public ?string $password = '';

    public ?string $passwordConfirmation = '';

    #[Locked]
    public ?string $token = null;

    public function mount(?string $email = null, ?string $token = null): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->token = $token ?? request()->query('token');

        $this->form->fill([
            'email' => $email ?? request()->query('email'),
        ]);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $tooManyRequestsException) {
            Notification::make()
                ->title(__('filament-panels::auth/pages/password-reset/reset-password.notifications.throttled.title', [
                    'seconds' => $tooManyRequestsException->secondsUntilAvailable,
                    'minutes' => ceil($tooManyRequestsException->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::auth/pages/password-reset/reset-password.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/password-reset/reset-password.notifications.throttled.body', [
                    'seconds' => $tooManyRequestsException->secondsUntilAvailable,
                    'minutes' => ceil($tooManyRequestsException->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        $data['email'] = $this->email;
        $data['token'] = $this->token;

        $status = Password::broker(Filament::getAuthPasswordBroker())->reset(
            $data,
            function (CanResetPassword|Model|Authenticatable $user) use ($data): void {
                $this->resetUserPassword($user, $data['password']);
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()
                ->title(__($status))
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        }

        Notification::make()
            ->title(__($status))
            ->danger()
            ->send();

        return null;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.email.label'))
            ->disabled()
            ->autofocus();
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rules($this->getPasswordResetValidationRules())
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::auth/pages/password-reset/reset-password.form.password.validation_attribute'))
            ->helperText($this->getPasswordResetHelperText());
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return TextInput::make('passwordConfirmation')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.password_confirmation.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->dehydrated(false);
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::auth/pages/password-reset/reset-password.title');
    }

    #[Override]
    public function getHeading(): string|Htmlable
    {
        return __('filament-panels::auth/pages/password-reset/reset-password.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getResetPasswordFormAction(),
        ];
    }

    public function getResetPasswordFormAction(): Action
    {
        return Action::make('resetPassword')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.actions.reset.label'))
            ->submit('resetPassword');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_BEFORE),
                $this->getFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_PASSWORD_RESET_RESET_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('resetPassword')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),
            ]);
    }
}
