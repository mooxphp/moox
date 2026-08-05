<?php

declare(strict_types=1);

namespace Moox\LoginLink\Services;

use Filament\Models\Contracts\FilamentUser;
use Filament\PanelRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Moox\LoginLink\Mail\ProcessLinkMail;
use Moox\LoginLink\Models\LoginLink;
use Moox\LoginLink\Models\LoginLinkProcess;

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

        $this->issue(
            processSlug: RedemptionHandlerRegistry::DEFAULT_PROCESS,
            subject: $user,
            email: $user->email,
            panelId: $panelId,
            request: $request,
            setUserMorph: true,
        );

        return 'sent';
    }

    /**
     * Issue a new signed link for a process + subject. Invalidates prior valid links
     * for the same process + subject. Expiry/from/content come from the process definition.
     */
    public function issue(
        string $processSlug,
        Model $subject,
        string $email,
        string $panelId,
        Request $request,
        bool $setUserMorph = false,
    ): LoginLink {
        $process = $this->resolveProcessDefinition($processSlug);
        $expiresMinutes = $process?->resolveExpiryMinutes()
            ?? (int) config('login-link.expiration_minutes', 60);

        $this->invalidatePriorValidLinks($processSlug, $subject);

        $attributes = [
            'panel_id' => $panelId,
            'process' => $processSlug,
            'subject_id' => $subject->getKey(),
            'subject_type' => $subject::class,
            'email' => $email,
            'expires_at' => now()->addMinutes($expiresMinutes),
            'used_at' => null,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ];

        if ($setUserMorph) {
            $attributes['user_id'] = $subject->getKey();
            $attributes['user_type'] = $subject::class;
        }

        $loginLink = LoginLink::query()->create($attributes);

        Mail::to($email)->queue(new ProcessLinkMail($loginLink, $process));

        return $loginLink;
    }

    /**
     * Re-issue the current link for a process + subject (invalidates prior, creates + mails a new one).
     */
    public function resend(
        string $processSlug,
        Model $subject,
        string $email,
        string $panelId,
        Request $request,
        bool $setUserMorph = false,
    ): LoginLink {
        return $this->issue(
            processSlug: $processSlug,
            subject: $subject,
            email: $email,
            panelId: $panelId,
            request: $request,
            setUserMorph: $setUserMorph,
        );
    }

    /**
     * Resend from an existing link instance (uses its process + subject).
     */
    public function resendLink(LoginLink $loginLink, Request $request): ?LoginLink
    {
        $subject = $loginLink->subject()->first() ?? $loginLink->user()->first();

        if (! $subject instanceof Model) {
            return null;
        }

        $setUserMorph = $loginLink->user_type !== null && $loginLink->user_id !== null;

        return $this->resend(
            processSlug: $loginLink->process ?: RedemptionHandlerRegistry::DEFAULT_PROCESS,
            subject: $subject,
            email: (string) $loginLink->email,
            panelId: (string) $loginLink->panel_id,
            request: $request,
            setUserMorph: $setUserMorph,
        );
    }

    public function invalidatePriorValidLinks(string $processSlug, Model $subject): void
    {
        LoginLink::query()
            ->where('process', $processSlug)
            ->where(function ($query) use ($subject): void {
                $query
                    ->where(function ($inner) use ($subject): void {
                        $inner
                            ->where('subject_type', $subject::class)
                            ->where('subject_id', $subject->getKey());
                    })
                    ->orWhere(function ($inner) use ($subject): void {
                        $inner
                            ->where('user_type', $subject::class)
                            ->where('user_id', $subject->getKey());
                    });
            })
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['used_at' => now()]);
    }

    public function resolveProcessDefinition(string $processSlug): ?LoginLinkProcess
    {
        return LoginLinkProcess::query()->where('slug', $processSlug)->first();
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
