<?php

namespace Moox\User\Services;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\SimplePage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Jenssegers\Agent\Agent;

/**
 * @property Form $form
 */
class Login extends SimplePage
{
    use InteractsWithFormActions, WithRateLimiting;

    protected $userDeviceTracker;

    protected $sessionRelationService;

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::pages.auth.login';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public function __construct()
    {
        if (class_exists(\Moox\UserDevice\Services\UserDeviceTracker::class)) {
            $this->userDeviceTracker = app(\Moox\UserDevice\Services\UserDeviceTracker::class);
        }

        if (class_exists(\Moox\UserSession\Services\SessionRelationService::class)) {
            $this->sessionRelationService = app(\Moox\UserSession\Services\SessionRelationService::class);
        }
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getLoginFormComponent(): Component
    {
        return
            TextInput::make('login')
                ->label('Login')
                ->required()
                ->autocomplete()
                ->autofocus()
                ->extraInputAttributes(['tabindex' => 1]);
    }

    public function authenticate(): Redirector|RedirectResponse|LoginResponse|null
    {
        if (! $this->isWhitelisted()) {
            try {
                $this->rateLimit(5);
            } catch (TooManyRequestsException $exception) {
                Notification::make()
                    ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                        'seconds' => $exception->secondsUntilAvailable,
                        'minutes' => ceil($exception->secondsUntilAvailable / 60),
                    ]))
                    ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                        'seconds' => $exception->secondsUntilAvailable,
                        'minutes' => $exception->minutesUntilAvailable,
                    ]) : null)
                    ->danger()
                    ->send();

                return null;
            }
        }

        $guardName = Filament::getAuthGuard();
        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $credentialKey = array_key_first($credentials);
        $guardProvider = config("auth.guards.$guardName.provider");
        $userModel = config("auth.providers.$guardProvider.model");
        $userModelUsername = config("user.auth.$guardName.username");
        $userModelEmail = config("user.auth.$guardName.email");
        $query = $userModel::query();

        if (! empty($userModelUsername) && $credentialKey === 'name') {
            $query->where($userModelUsername, $credentials[$credentialKey]);
        }

        if (! empty($userModelEmail) && $credentialKey === 'email') {
            if ($query->getQuery()->wheres) {
                $query->orWhere($userModelEmail, $credentials[$credentialKey]);
            } else {
                $query->where($userModelEmail, $credentials[$credentialKey]);
            }
        }

        $user = $query->first();

        if (! Auth::guard($guardName)->attempt($credentials, $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        Auth::guard($guardName)->login($user, $data['remember'] ?? false);

        session()->regenerate();
        session()->save();

        if ($this->sessionRelationService) {
            $this->sessionRelationService->associateUserSession($user);
        }

        if ($this->userDeviceTracker) {
            $this->userDeviceTracker->addUserDevice(request(), $user, app(Agent::class));
        }

        return app(LoginResponse::class);
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::pages/auth/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->rules(config('user.password.validation'));
        // TODO: How to solve validation messages
        // ->validationMessages([]);
    }

    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::pages/auth/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::pages/auth/login.title');
    }

    public function getHeading(): string|Htmlable
    {
        return __('filament-panels::pages/auth/login.heading');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getAuthenticateFormAction(),
        ];
    }

    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::pages/auth/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    private function isWhitelisted(): bool
    {
        $ipAddress = request()->ip();

        $ipWhiteList = config('user.ip_whitelist');

        if (isset($ipWhiteList) && ! empty($ipWhiteList)) {
            if (is_array($ipWhiteList) && in_array($ipAddress, $ipWhiteList)) {
                return true;
            }
            if ($ipWhiteList === $ipAddress) {
                return true;
            }
        }

        return false;
    }
}
