<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
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

it('requires a mapped shield permission when permissions.required is true', function (): void {
    $user = new class implements Authenticatable, Authorizable
    {
        /** @var list<string> */
        public array $abilities = ['Impersonate:PortalUser'];

        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): mixed
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): ?string
        {
            return null;
        }

        public function setRememberToken($value): void
        {
        }

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }

        public function can($abilities, $arguments = []): bool
        {
            $abilities = is_array($abilities) ? $abilities : [$abilities];

            foreach ($abilities as $ability) {
                if (! in_array($ability, $this->abilities, true)) {
                    return false;
                }
            }

            return true;
        }

        public function cannot($abilities, $arguments = []): bool
        {
            return ! $this->can($abilities, $arguments);
        }
    };

    $config = [
        'enabled' => true,
        'targets' => ['finance', 'portal'],
        'permissions' => [
            'required' => true,
            'targets' => [
                'portal' => 'Impersonate:PortalUser',
                'finance' => 'Impersonate:FinanceUser',
            ],
        ],
    ];

    expect(ImpersonateAction::userMay($user, 'portal', $config))->toBeTrue()
        ->and(ImpersonateAction::userMay($user, 'finance', $config))->toBeFalse()
        ->and(ImpersonateAction::userMay($user, null, $config))->toBeTrue();

    $config['permissions']['required'] = false;

    expect(ImpersonateAction::userMay($user, 'finance', $config))->toBeTrue();
});

it('denies userMay when there is no user or permissions are required without a match', function (): void {
    $config = [
        'enabled' => true,
        'targets' => ['portal'],
        'permissions' => [
            'required' => true,
            'targets' => [
                'portal' => 'Impersonate:PortalUser',
            ],
        ],
    ];

    expect(ImpersonateAction::userMay(null, 'portal', $config))->toBeFalse();
});
