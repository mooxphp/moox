<?php

namespace Moox\Security\Services;

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
use Moox\UserDevice\Services\LocationService;
use Moox\UserDevice\Traits\TrackDevices;
use Moox\UserSession\Models\UserSession;

/**
 * @property Form $form
 */
class Login extends SimplePage
{
    use InteractsWithFormActions, TrackDevices, WithRateLimiting;

    /**
     * @var view-string
     */
    protected static string $view = 'filament-panels::pages.auth.login';

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
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->danger()
                ->send();

            return null;
        }

        $guardName = Filament::getAuthGuard();
        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $credentialKey = array_key_first($credentials);
        $guardProvider = config("auth.guards.$guardName.provider");
        $userModel = config("auth.providers.$guardProvider.model");
        $userModelUsername = config("security.auth.$guardName.username");
        $userModelEmail = config("security.auth.$guardName.email");
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

        if (config('security.wpModel') && $user instanceof (config('security.wpModel'))) {
            $wpAuthService = new \Moox\Security\Services\WordPressAuthService;

            if (! $wpAuthService->checkPassword($credentials['password'], $user->user_pass)) {
                $this->throwFailureValidationException();
            }
        } else {
            if (! Auth::guard($guardName)->attempt($credentials, $data['remember'] ?? false)) {
                $this->throwFailureValidationException();
            }
        }

        Auth::guard($guardName)->login($user, $data['remember'] ?? false);

        session()->regenerate();
        session()->save();

        $this->associateUserSession($user);

        $this->addUserDevice(request(), $user, app(Agent::class), app(LocationService::class));

        if (config('security.wpModel') && $user instanceof (config('security.wpModel'))
             && config('press.auth_wordpress') === true) {
            $payload = base64_encode($user->ID);
            $signature = hash_hmac('sha256', $payload, env('APP_KEY'));
            $token = "{$payload}.{$signature}";

            return redirect('https://'.$_SERVER['SERVER_NAME'].config('press.wordpress_slug').'/wp-login.php?auth_token='.$token);
        } else {
            return app(LoginResponse::class);
        }
    }

    // TODO: Move to a service
    // TODO: Test Login for Moox and Press
    // TODO: When redirecting to WordPress, the user_id is not set
    // TODO: When logged in with /press, /press/login should not redirect to moox/login
    // TODO: Whether all in Security or Class Exists
    // TODO: When model is missing or does not exist, sessions should work but show the problem
    protected function associateUserSession($user): void
    {
        try {
            $sessionId = session()->getId();
            $userType = get_class($user);

            $userSession = UserSession::find($sessionId);

            if ($userSession) {
                $userSession->update([
                    'user_type' => $userType,
                    'user_id' => $user->id,
                    'last_activity' => now()->getTimestamp(),
                ]);
            } else {
                \Log::warning('Session not found for ID:', ['session_id' => $sessionId]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to associate user session:', ['error' => $e->getMessage()]);
        }
    }

    // TODO: Add devices here or in a separate service

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
            ->rules(config('security.password.validation'))
            ->validationMessages([
                'min' => 'Dein Passwort entspricht nicht unserer Passwortqualität - Bitte ändere dein Passwort!',
                'max' => 'Dein Passwort entspricht nicht unserer Passwortqualität - Bitte ändere dein Passwort!',
                'password.symbols' => 'Dein Passwort entspricht nicht unserer Passwortqualität - Bitte ändere dein Passwort!',
                'password.mixed' => 'Dein Passwort entspricht nicht unserer Passwortqualität - Bitte ändere dein Passwort!',
                'password.numbers' => 'Dein Passwort entspricht nicht unserer Passwortqualität - Bitte ändere dein Passwort!',
                'password.uncompromised' => 'Dein Passwort entspricht nicht unserer Passwortqualität - Bitte ändere dein Passwort!',
            ]);
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
}
