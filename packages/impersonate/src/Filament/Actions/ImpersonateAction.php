<?php

declare(strict_types=1);

namespace Moox\Impersonate\Filament\Actions;

use STS\FilamentImpersonate\Actions\Impersonate;

/**
 * Thin factory around stechstudio/filament-impersonate.
 *
 * Soft-coupling for consumers (no hard require):
 *   if (! class_exists(ImpersonateAction::class)) { return null; }
 */
final class ImpersonateAction
{
    public static function make(string $name = 'impersonate'): Impersonate
    {
        return Impersonate::make($name)->withoutSpa();
    }
}
