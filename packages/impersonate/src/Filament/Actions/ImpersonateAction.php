<?php

declare(strict_types=1);

namespace Moox\Impersonate\Filament\Actions;

use STS\FilamentImpersonate\Actions\Impersonate;

/**
 * Thin factory around stechstudio/filament-impersonate.
 *
 * Soft-coupling for consumers (no hard require):
 *   if (! class_exists(ImpersonateAction::class) || ! ImpersonateAction::isEnabled('finance')) {
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
