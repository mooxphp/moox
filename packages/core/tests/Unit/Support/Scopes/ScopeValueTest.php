<?php

declare(strict_types=1);

use Moox\Core\Support\Scopes\ScopeValue;

it('parses a four-part scope string', function (): void {
    $scope = ScopeValue::parse('media:tag:blog:private');

    expect($scope)->not->toBeNull()
        ->and($scope->origin())->toBe('media')
        ->and($scope->source())->toBe('tag')
        ->and($scope->context())->toBe('blog')
        ->and($scope->boundary())->toBe('private')
        ->and((string) $scope)->toBe('media:tag:blog:private');
});

it('treats blank scopes as null', function (): void {
    expect(ScopeValue::parse(null))->toBeNull()
        ->and(ScopeValue::parse(''))->toBeNull();
});

it('rejects malformed scope strings', function (): void {
    ScopeValue::parse('media:tag:blog');
})->throws(InvalidArgumentException::class);

it('exposes allowed boundaries', function (): void {
    expect(ScopeValue::allowedBoundaries())->toContain(
        ScopeValue::MODE_PRIVATE,
        ScopeValue::MODE_PUBLIC,
        ScopeValue::MODE_USER,
        ScopeValue::MODE_USER_TYPE,
        ScopeValue::MODE_GROUP,
    );
});
