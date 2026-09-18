<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Moox\Core\Models\Concerns\HasScopedModel;
use Moox\Core\Services\ScopeAssignmentValidator;
use Tests\TestCase;

uses(TestCase::class);

it('always allows assigning the global unassigned scope', function (): void {
    $record = new class extends Model
    {
        use HasScopedModel;

        protected $table = 'media';

        public function resolveScopeOrigin(): string
        {
            return 'media';
        }
    };

    $result = app(ScopeAssignmentValidator::class)->validate($record, '', null);

    expect($result['allowed'])->toBeTrue();
});

it('rejects concrete scopes when the scopes catalog table is missing', function (): void {
    Schema::shouldReceive('hasTable')
        ->with('scopes')
        ->andReturn(false);

    $record = new class extends Model
    {
        use HasScopedModel;

        protected $table = 'media';

        public function resolveScopeOrigin(): string
        {
            return 'media';
        }
    };

    $user = new class implements Authenticatable
    {
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
    };

    $result = app(ScopeAssignmentValidator::class)->validate(
        $record,
        'media:tag:blog:private',
        $user,
    );

    expect($result['allowed'])->toBeFalse()
        ->and($result['reason'] ?? null)->toBe('Target scope is not active.');
});
