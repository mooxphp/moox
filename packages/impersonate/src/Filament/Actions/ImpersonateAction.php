<?php

declare(strict_types=1);

namespace Moox\Impersonate\Filament\Actions;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use STS\FilamentImpersonate\Actions\Impersonate;

/**
 * Thin factory around stechstudio/filament-impersonate.
 *
 * Soft-coupling for consumers (no hard require):
 *   if (! class_exists(ImpersonateAction::class) || ! ImpersonateAction::userMay($user, 'finance')) {
 *       return null;
 *   }
 */
final class ImpersonateAction
{
    /**
     * Whether impersonation is allowed globally and optionally for a target key
     * (typically a panel or guard name listed in impersonate.targets).
     *
     * @param  array{enabled?: mixed, targets?: list<string>|array<string, mixed>}|null  $config
     */
    public static function isEnabled(?string $target = null, ?array $config = null): bool
    {
        $config ??= [
            'enabled' => config('impersonate.enabled', true),
            'targets' => config('impersonate.targets', []),
        ];

        if (! filter_var($config['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if ($target === null || $target === '') {
            return true;
        }

        $targets = self::normalizeTargets($config['targets'] ?? []);

        return in_array($target, $targets, true);
    }

    /**
     * Config allowlist plus optional Shield/Spatie permission for the target.
     *
     * @param  array{
     *     enabled?: mixed,
     *     targets?: list<string>|array<string, mixed>,
     *     permissions?: array{required?: mixed, targets?: array<string, string>}
     * }|null  $config
     */
    public static function userMay(?Authenticatable $user, ?string $target = null, ?array $config = null): bool
    {
        if ($user === null) {
            return false;
        }

        $config ??= [
            'enabled' => config('impersonate.enabled', true),
            'targets' => config('impersonate.targets', []),
            'permissions' => config('impersonate.permissions', []),
        ];

        if (! self::isEnabled($target, $config)) {
            return false;
        }

        $permissions = is_array($config['permissions'] ?? null) ? $config['permissions'] : [];

        if (! filter_var($permissions['required'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if (! $user instanceof Authorizable) {
            return false;
        }

        $map = is_array($permissions['targets'] ?? null) ? $permissions['targets'] : [];

        if ($target !== null && $target !== '') {
            $permission = $map[$target] ?? null;

            return is_string($permission) && $permission !== '' && $user->can($permission);
        }

        foreach ($map as $permission) {
            if (is_string($permission) && $permission !== '' && $user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private static function normalizeTargets(mixed $targets): array
    {
        if (! is_array($targets)) {
            return [];
        }

        // Allowlist: ['finance', 'portal']
        if (array_is_list($targets)) {
            return array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), $targets),
                static fn (string $value): bool => $value !== '',
            ));
        }

        // Legacy map: ['finance' => true, 'portal' => false]
        $allowed = [];

        foreach ($targets as $key => $enabled) {
            if (filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
                $allowed[] = (string) $key;
            }
        }

        return $allowed;
    }

    public static function make(string $name = 'impersonate'): Impersonate
    {
        return Impersonate::make($name)->withoutSpa();
    }
}
