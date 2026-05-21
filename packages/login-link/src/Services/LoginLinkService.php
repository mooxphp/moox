<?php

namespace Moox\LoginLink\Services;

use Filament\Models\Contracts\FilamentUser;
use Filament\PanelRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Moox\LoginLink\Mail\LoginLinkEmail;
use Moox\LoginLink\Models\LoginLink;

class LoginLinkService
{
    /**
     * @return 'sent'|'not_found'|'denied'
     */
    public function sendForEmail(string $panelId, string $guardName, string $email, Request $request): string
    {
        $userModel = $this->resolveGuardUserModel($guardName);
        if (! $userModel) {
            return 'not_found';
        }

        $allowedModels = array_values(config('login-link.user_models', []));
        if (! in_array($userModel, $allowedModels, true)) {
            return 'not_found';
        }

        $user = $this->findUserByEmail($userModel, $guardName, $email);
        if (! $user) {
            return 'not_found';
        }

        if (! $this->userCanAccessPanel($user, $panelId)) {
            return 'denied';
        }

        $expiresMinutes = (int) config('login-link.expiration_minutes', 60);

        LoginLink::query()
            ->where('panel_id', $panelId)
            ->where('user_type', $user::class)
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);

        $loginLink = LoginLink::create([
            'panel_id' => $panelId,
            'user_id' => $user->id,
            'user_type' => $user::class,
            'email' => $user->email,
            'expires_at' => now()->addMinutes($expiresMinutes),
            'used_at' => null,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        Mail::to($user->email)->queue(new LoginLinkEmail($loginLink));

        return 'sent';
    }

    private function userCanAccessPanel(mixed $user, string $panelId): bool
    {
        if (! $user instanceof FilamentUser) {
            return false;
        }

        $panel = app(PanelRegistry::class)->get($panelId);
        if (! $panel) {
            return false;
        }

        return $user->canAccessPanel($panel);
    }

    /**
     * @return class-string|null
     */
    public function resolveEmailForIdentifier(string $guardName, string $identifier): ?string
    {
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return mb_strtolower($identifier);
        }

        $userModel = $this->resolveGuardUserModel($guardName);
        if (! $userModel) {
            return null;
        }

        $allowedModels = array_values(config('login-link.user_models', []));
        if (! in_array($userModel, $allowedModels, true)) {
            return null;
        }

        $usernameColumn = config(sprintf('user.auth.%s.username', $guardName));
        if (! is_string($usernameColumn) || $usernameColumn === '') {
            return null;
        }

        $user = $userModel::query()->where($usernameColumn, $identifier)->first();
        $emailColumn = $this->resolveEmailColumn($guardName);

        if (! $user || ! isset($user->{$emailColumn})) {
            return null;
        }

        $email = trim((string) $user->{$emailColumn});

        return $email !== '' ? mb_strtolower($email) : null;
    }

    /**
     * @param  class-string  $userModel
     */
    private function findUserByEmail(string $userModel, string $guardName, string $email): mixed
    {
        $emailColumn = $this->resolveEmailColumn($guardName);

        return $userModel::query()->where($emailColumn, $email)->first();
    }

    private function resolveEmailColumn(string $guardName): string
    {
        $column = config(sprintf('user.auth.%s.email', $guardName));

        return is_string($column) && $column !== '' ? $column : 'email';
    }

    /**
     * @return class-string|null
     */
    private function resolveGuardUserModel(string $guardName): ?string
    {
        $provider = config('auth.guards.'.$guardName.'.provider');
        $model = $provider ? config('auth.providers.'.$provider.'.model') : null;

        if (is_string($model) && class_exists($model)) {
            return $model;
        }

        return null;
    }
}
