<?php

namespace Moox\LoginLink\Plugins;

use Filament\Auth\Pages\Login as FilamentLogin;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use Illuminate\Support\Facades\Route;
use Moox\LoginLink\Http\Controllers\LoginLinkRedemptionController;
use Moox\LoginLink\Http\Middleware\AttemptLoginLinkRedemption;
use Moox\LoginLink\Resources\LoginLinkResource;
use Moox\LoginLink\Support\PanelLoginEnhancer;

class LoginLinkPlugin implements Plugin
{
    use EvaluatesClosures;

    public function getId(): string
    {
        return 'login-link';
    }

    public function register(Panel $panel): void
    {
        if (! (bool) config('login-link.passwordless.enabled', false)) {
            return;
        }

        $panel->resources([
            LoginLinkResource::class,
        ]);

        $this->enhancePanelLogin($panel);

        $panel->middleware([
            AttemptLoginLinkRedemption::class,
        ], isPersistent: true);

        $panel->routes(function (): void {
            Route::get('login-link/{loginLink}', LoginLinkRedemptionController::class)
                ->middleware(['signed', 'throttle:10,1'])
                ->name('auth.login-link.consume');
        });
    }

    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Adds the login-link hint to whatever login class the panel already uses.
     */
    protected function enhancePanelLogin(Panel $panel): void
    {
        $baseLogin = $panel->getLoginRouteAction() ?? FilamentLogin::class;
        $enhancedLogin = PanelLoginEnhancer::resolve($baseLogin);

        if ($enhancedLogin !== $baseLogin) {
            $panel->login($enhancedLogin);
        }
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
