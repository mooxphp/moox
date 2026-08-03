<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Moox\Media\Models\Media;
use Moox\Media\Policies\MediaPolicy;
use Tests\TestCase;

uses(TestCase::class);

function mediaAuthUser(): Authenticatable
{
    return new class implements Authenticatable
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
}

function mediaFromAttributes(array $attributes): Media
{
    $media = new Media;
    $media->setRawAttributes($attributes, true);

    return $media;
}

it('allows update and delete for unprotected media', function (): void {
    $policy = new MediaPolicy;
    $user = mediaAuthUser();
    $media = mediaFromAttributes([
        'id' => 1,
        'write_protected' => false,
        'file_name' => 'a.jpg',
    ]);

    expect($policy->update($user, $media))->toBeTrue()
        ->and($policy->delete($user, $media))->toBeTrue();
});

it('denies update and delete for write-protected media', function (): void {
    $policy = new MediaPolicy;
    $user = mediaAuthUser();
    $media = mediaFromAttributes([
        'id' => 1,
        'write_protected' => true,
        'file_name' => 'locked.jpg',
    ]);

    expect($policy->update($user, $media))->toBeFalse()
        ->and($policy->delete($user, $media))->toBeFalse();
});
