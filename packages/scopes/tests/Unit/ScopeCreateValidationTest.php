<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Moox\Core\Models\Concerns\HasScopedModel;
use Moox\Core\Services\ScopeRegistry;
use Moox\Scopes\Entities\Scopes\ScopeResource;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function (): void {
    $originModel = new class extends Model
    {
        use HasScopedModel;

        protected $table = 'media';
    };

    $originClass = $originModel::class;

    config([
        'core.packages' => [
            'tag' => [],
        ],
        'tag.resources' => [
            'tag' => [
                'context' => 'blog',
                'scopes' => [
                    'allowed' => [
                        'media_private' => [
                            'origin' => 'media',
                            'resource' => 'Moox\\Media\\Resources\\MediaResource',
                        ],
                    ],
                    'registry' => [
                        'origins' => [
                            'media' => $originClass,
                        ],
                        'sources' => [
                            'tag' => 'App\\Models\\Tag',
                        ],
                    ],
                ],
            ],
        ],
        'core.scopes' => [],
    ]);

    app()->forgetInstance(ScopeRegistry::class);
    app(ScopeRegistry::class)->flush();
});

it('rejects create payloads outside the config whitelist', function (): void {
    expect(fn () => ScopeResource::assertCreatePayloadIsAllowed([
        'origin' => 'media',
        'source' => 'unknown',
        'context' => 'blog',
        'boundary' => 'private',
        'scope' => 'media:unknown:blog:private',
    ]))->toThrow(ValidationException::class);
});

it('rejects tampered scope keys that do not match the parts', function (): void {
    expect(fn () => ScopeResource::assertCreatePayloadIsAllowed([
        'origin' => 'media',
        'source' => 'tag',
        'context' => 'blog',
        'boundary' => 'private',
        'scope' => 'media:tag:other:private',
    ]))->toThrow(ValidationException::class);
});

it('accepts create payloads that match registry and allowed sources', function (): void {
    ScopeResource::assertCreatePayloadIsAllowed([
        'origin' => 'media',
        'source' => 'tag',
        'context' => 'blog',
        'boundary' => 'private',
        'scope' => 'media:tag:blog:private',
    ]);

    expect(true)->toBeTrue();
});
