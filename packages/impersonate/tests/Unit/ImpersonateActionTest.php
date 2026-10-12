<?php

declare(strict_types=1);

use Moox\Impersonate\Filament\Actions\ImpersonateAction;

it('exposes the ImpersonateAction helper', function (): void {
    expect(class_exists(ImpersonateAction::class))->toBeTrue();
});

it('respects the global switch and allowlisted targets', function (): void {
    $config = [
        'enabled' => true,
        'targets' => ['finance', 'portal'],
    ];

    expect(ImpersonateAction::isEnabled(null, $config))->toBeTrue()
        ->and(ImpersonateAction::isEnabled('finance', $config))->toBeTrue()
        ->and(ImpersonateAction::isEnabled('portal', $config))->toBeTrue()
        ->and(ImpersonateAction::isEnabled('admin', $config))->toBeFalse();

    $config['targets'] = ['finance'];

    expect(ImpersonateAction::isEnabled('portal', $config))->toBeFalse();

    $config['enabled'] = false;

    expect(ImpersonateAction::isEnabled(null, $config))->toBeFalse()
        ->and(ImpersonateAction::isEnabled('finance', $config))->toBeFalse();
});
