<?php

declare(strict_types=1);

use Moox\Impersonate\Filament\Actions\ImpersonateAction;
use STS\FilamentImpersonate\Actions\Impersonate;

it('exposes the STS Impersonate action factory', function (): void {
    expect(class_exists(ImpersonateAction::class))->toBeTrue()
        ->and(class_exists(Impersonate::class))->toBeTrue();
});
