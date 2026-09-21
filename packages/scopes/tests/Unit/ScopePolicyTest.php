<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Moox\Scopes\Policies\ScopePolicy;

it('allows authenticated users when the permission system is unavailable', function (): void {
    $policy = new class extends ScopePolicy
    {
        protected function permissionSystemAvailable(): bool
        {
            return false;
        }
    };

    $user = new class implements Authorizable
    {
        public function can($abilities, $arguments = []): bool
        {
            return true;
        }

        public function cannot($abilities, $arguments = []): bool
        {
            return false;
        }
    };

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});

it('allows users who have no roles assigned yet', function (): void {
    $policy = new class extends ScopePolicy
    {
        protected function permissionSystemAvailable(): bool
        {
            return true;
        }
    };

    $user = new class implements Authorizable
    {
        public function can($abilities, $arguments = []): bool
        {
            return true;
        }

        public function cannot($abilities, $arguments = []): bool
        {
            return false;
        }

        public function hasRole($roles, ?string $guard = null): bool
        {
            return false;
        }

        public function getRoleNames(): Collection
        {
            return collect();
        }
    };

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});

it('denies non-admin users that already have roles', function (): void {
    $policy = new class extends ScopePolicy
    {
        protected function permissionSystemAvailable(): bool
        {
            return true;
        }

        protected function isShieldAdmin(Authorizable $user): bool
        {
            return false;
        }
    };

    $user = new class implements Authorizable
    {
        public function can($abilities, $arguments = []): bool
        {
            return true;
        }

        public function cannot($abilities, $arguments = []): bool
        {
            return false;
        }

        public function hasRole($roles, ?string $guard = null): bool
        {
            return false;
        }

        public function getRoleNames(): Collection
        {
            return collect(['editor']);
        }
    };

    $record = new class extends Model
    {
    };

    expect($policy->viewAny($user))->toBeFalse()
        ->and($policy->update($user, $record))->toBeFalse()
        ->and($policy->delete($user, $record))->toBeFalse();
});

it('allows shield admins when roles are in use', function (): void {
    $policy = new class extends ScopePolicy
    {
        protected function permissionSystemAvailable(): bool
        {
            return true;
        }

        protected function isShieldAdmin(Authorizable $user): bool
        {
            return true;
        }
    };

    $user = new class implements Authorizable
    {
        public function can($abilities, $arguments = []): bool
        {
            return true;
        }

        public function cannot($abilities, $arguments = []): bool
        {
            return false;
        }

        public function hasRole($roles, ?string $guard = null): bool
        {
            return true;
        }

        public function getRoleNames(): Collection
        {
            return collect(['super_admin']);
        }
    };

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue();
});
