<?php

namespace Moox\Press\Services;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\MultiFactorAuthenticationProvider;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Jenssegers\Agent\Agent;
use Livewire\Attributes\Locked;
use Moox\UserDevice\Services\UserDeviceTracker;
use Moox\UserSession\Services\SessionRelationService;

/**
 * @property-read Action $registerAction
 * @property-read Schema $form
 * @property-read Schema $multiFactorChallengeForm
 */
class Login extends SimplePage
{
    use WithRateLimiting;

    protected $userDeviceTracker;

    protected $sessionRelationService;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    #[Locked]
    public ?string $userUndertakingMultiFactorAuthentication = null;

    public function __construct()
    {
        if (class_exists(UserDeviceTracker::class)) {
            $this->userDeviceTracker = app(UserDeviceTracker::class);
        }

        if (class_exists(SessionRelationService::class)) {
            $this->sessionRelationService = app(SessionRelationService::class);
        }
    }

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    public function authenticate(): Redirector|RedirectResponse|LoginResponse|null
    {
        if (! $this->isWhitelisted()) {
            try {
                $this->rateLimit(5);
            } catch (TooManyRequestsException $exception) {
                $this->getRateLimitedNotification($exception)?->send();

                return null;
            }
        }

        $guardName = Filament::getAuthGuard();
        $data = $this->form->getState();
        $credentials = $this->getCredentialsFromFormData($data);
        $credentialKey = array_key_first($credentials);
        $guardProvider = config(sprintf('auth.guards.%s.provider', $guardName));
        $userModel = config(sprintf('auth.providers.%s.model', $guardProvider));
        $userModelUsername = config(sprintf('press.auth.%s.username', $guardName));
        $userModelEmail = config(sprintf('press.auth.%s.email', $guardName));
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

        if (config('press.wpModel') && $user instanceof (config('press.wpModel'))) {
            $wpAuthService = new WordPressAuthService;
            if (! $wpAuthService->checkPassword($credentials['password'], $user->user_pass)) {
                $this->throwFailureValidationException();
            }
        } elseif (! Auth::guard($guardName)->attempt($credentials, $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        if (
            filled($this->userUndertakingMultiFactorAuthentication) &&
            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
        ) {
            $this->multiFactorChallengeForm->validate();
        } else {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($user);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
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

        if (
            config('press.wpModel') && $user instanceof (config('press.wpModel'))
            && config('press.auth_wordpress') === true
        ) {
            $payload = base64_encode($user->ID);
            $signature = hash_hmac('sha256', $payload, (string) config('app.key'));
            $token = sprintf('%s.%s', $payload, $signature);

            $redirectTarget = config('press.redirect_after_login', 'wp-admin');
            $redirectParam = $redirectTarget === 'frontend' ? '&redirect_to=frontend' : '';

            if ($data['remember'] ?? false) {
                return redirect('https://'.$_SERVER['SERVER_NAME'].config('press.wordpress_slug').'/wp-login.php?auth_token='.$token.'&remember_me=true'.$redirectParam);
            } else {
                return redirect('https://'.$_SERVER['SERVER_NAME'].config('press.wordpress_slug').'/wp-login.php?auth_token='.$token.$redirectParam);
            }
        } else {
            return app(LoginResponse::class);
        }
    }

    public function defaultMultiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema
            ->components(function (): array {
                if (blank($this->userUndertakingMultiFactorAuthentication)) {
                    return [];
                }

                $authProvider = Filament::auth()->getProvider(); /** @phpstan-ignore-line */
                $user = $authProvider->retrieveById(decrypt($this->userUndertakingMultiFactorAuthentication));

                $enabledMultiFactorAuthenticationProviders = array_filter(
                    Filament::getMultiFactorAuthenticationProviders(),
                    fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): bool => $multiFactorAuthenticationProvider->isEnabled($user)
                );

                return [
                    ...Arr::wrap($this->getMultiFactorProviderFormComponent()),
                    ...collect($enabledMultiFactorAuthenticationProviders)
                        ->map(fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): Component => Group::make($multiFactorAuthenticationProvider->getChallengeFormComponents($user))
                            ->statePath($multiFactorAuthenticationProvider->getId())
                            ->when(
                                count($enabledMultiFactorAuthenticationProviders) > 1,
                                fn (Group $group) => $group->visible(fn (Get $get): bool => $get('provider') === $multiFactorAuthenticationProvider->getId())
                            ))
                        ->all(),
                ];
            })
            ->statePath('data.multiFactor');
    }

    public function multiFactorChallengeForm(Schema $schema): Schema
    {
        return $schema;
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/login.form.email.label'))
            ->email()
            ->required()
            ->autocomplete()
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
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

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::auth/pages/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2])
            ->rules(config('press.password.validation'))
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
            ->label(__('filament-panels::auth/pages/login.form.remember.label'));
    }

    protected function getMultiFactorProviderFormComponent(): ?Component
    {
        $authProvider = Filament::auth()->getProvider(); /** @phpstan-ignore-line */
        $user = $authProvider->retrieveById(decrypt($this->userUndertakingMultiFactorAuthentication));

        $enabledMultiFactorAuthenticationProviders = array_filter(
            Filament::getMultiFactorAuthenticationProviders(),
            fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): bool => $multiFactorAuthenticationProvider->isEnabled($user)
        );

        if (count($enabledMultiFactorAuthenticationProviders) <= 1) {
            return null;
        }

        return Section::make()
            ->compact()
            ->secondary()
            ->schema(fn (Section $section): array => [
                Radio::make('provider')
                    ->label(__('filament-panels::auth/pages/login.multi_factor.form.provider.label'))
                    ->options(array_map(
                        fn (MultiFactorAuthenticationProvider $multiFactorAuthenticationProvider): string => $multiFactorAuthenticationProvider->getLoginFormLabel(),
                        $enabledMultiFactorAuthenticationProviders,
                    ))
                    ->live()
                    ->afterStateUpdated(function (?string $state) use ($enabledMultiFactorAuthenticationProviders, $section, $user): void {
                        $provider = $enabledMultiFactorAuthenticationProviders[$state] ?? null;

                        if (! $provider) {
                            return;
                        }

                        $section
                            ->getContainer()
                            ->getComponent($provider->getId())
                            ->getChildSchema()
                            ->fill();

                        if (! ($provider instanceof HasBeforeChallengeHook)) {
                            return;
                        }

                        $provider->beforeChallenge($user);
                    })
                    ->default(array_key_first($enabledMultiFactorAuthenticationProviders))
                    ->required()
                    ->markAsRequired(false),
            ]);
    }

    public function registerAction(): Action
    {
        return Action::make('register')
            ->link()
            ->label(__('filament-panels::auth/pages/login.actions.register.label'))
            ->url(filament()->getRegistrationUrl());
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-panels::auth/pages/login.title');
    }

    public function getHeading(): string|Htmlable
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.heading');
        }

        return __('filament-panels::auth/pages/login.heading');
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
            ->label(__('filament-panels::auth/pages/login.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getMultiFactorChallengeFormActions(): array
    {
        return [
            $this->getMultiFactorAuthenticateFormAction(),
        ];
    }

    protected function getMultiFactorAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label(__('filament-panels::auth/pages/login.multi_factor.form.actions.authenticate.label'))
            ->submit('authenticate');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    protected function hasFullWidthMultiFactorChallengeFormActions(): bool
    {
        return $this->hasFullWidthFormActions();
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        $login_type = filter_var($data['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'name';

        return [
            $login_type => $data['login'],
            'password' => $data['password'],
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        if (! filament()->hasRegistration()) {
            return null;
        }

        return new HtmlString(__('filament-panels::auth/pages/login.actions.register.before').' '.$this->registerAction->toHtml());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE),
                $this->getFormContentComponent(),
                $this->getMultiFactorChallengeFormContentComponent(),
                RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions()),
            ])
            ->visible(fn (): bool => blank($this->userUndertakingMultiFactorAuthentication));
    }

    public function getMultiFactorChallengeFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('multiFactorChallengeForm')])
            ->id('multiFactorChallengeForm')
            ->livewireSubmitHandler('authenticate')
            ->footer([
                Actions::make($this->getMultiFactorChallengeFormActions())
                    ->alignment($this->getMultiFactorChallengeFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthMultiFactorChallengeFormActions()),
            ])
            ->visible(fn (): bool => filled($this->userUndertakingMultiFactorAuthentication));
    }

    public function getMultiFactorChallengeFormActionsAlignment(): string|Alignment
    {
        return $this->getFormActionsAlignment();
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return 'form';
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::auth/pages/login.notifications.throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::auth/pages/login.notifications.throttled') ?: []) ? __('filament-panels::auth/pages/login.notifications.throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.login' => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    private function isWhitelisted(): bool
    {
        $ipAddress = request()->ip();

        $ipWhiteList = config('press.ip_whitelist');

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
