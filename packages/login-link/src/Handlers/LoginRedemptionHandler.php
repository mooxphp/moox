<?php

declare(strict_types=1);

namespace Moox\LoginLink\Handlers;

use Filament\Models\Contracts\FilamentUser;
use Filament\PanelRegistry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Moox\LoginLink\Contracts\RedemptionHandler;
use Moox\LoginLink\Models\LoginLink;

class LoginRedemptionHandler implements RedemptionHandler
{
    public function handle(LoginLink $loginLink, ?string $panelId): ?RedirectResponse
    {
        if ($panelId === null || $panelId === '') {
            return null;
        }

        $panel = app(PanelRegistry::class)->get($panelId);

        if (! $panel) {
            return null;
        }

        $subject = $this->resolveSubject($loginLink, $panel->getAuthGuard());

        if ($subject === null) {
            return null;
        }

        if (! $this->subjectCanAccessPanel($subject, $panelId)) {
            return null;
        }

        Auth::guard($panel->getAuthGuard())->login($subject);
        session()->regenerate();
        session()->save();

        return redirect()->intended($panel->getUrl());
    }

    /**
     * Resolve the authenticatable subject via the panel's configured guard/model.
     * Prefers the polymorphic subject; falls back to the legacy user morph (BC).
     * The guard model must match so a non-User authenticatable works by configuration alone.
     */
    private function resolveSubject(LoginLink $loginLink, string $guardName): ?Authenticatable
    {
        $subject = $loginLink->subject()->first() ?? $loginLink->user()->first();

        if (! $subject instanceof Authenticatable) {
            return null;
        }

        $expectedModel = $this->resolveGuardUserModel($guardName);

        if ($expectedModel !== null && ! $subject instanceof $expectedModel) {
            return null;
        }

        return $subject;
    }

    /**
     * @return class-string<Authenticatable>|null
     */
    private function resolveGuardUserModel(string $guardName): ?string
    {
        $provider = config('auth.guards.'.$guardName.'.provider');
        $model = $provider ? config('auth.providers.'.$provider.'.model') : null;

        if (is_string($model) && class_exists($model)) {
            /** @var class-string<Authenticatable> $model */
            return $model;
        }

        return null;
    }

    private function subjectCanAccessPanel(mixed $subject, string $panelId): bool
    {
        if (! $subject instanceof FilamentUser) {
            return false;
        }

        $panel = app(PanelRegistry::class)->get($panelId);

        if (! $panel) {
            return false;
        }

        return $subject->canAccessPanel($panel);
    }
}
