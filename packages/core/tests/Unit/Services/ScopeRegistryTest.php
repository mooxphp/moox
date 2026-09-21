<?php

declare(strict_types=1);

use Moox\Core\Services\ScopeRegistry;
use Tests\TestCase;

uses(TestCase::class);

it('caches the merged registry until flushed', function (): void {
    config([
        'core.packages' => [
            'example' => [],
        ],
        'example.scopes.registry' => [
            'origins' => ['media' => 'App\\Models\\Media'],
            'sources' => ['tag' => 'App\\Models\\Tag'],
        ],
        'core.scopes' => [],
    ]);

    $registry = new ScopeRegistry;

    expect($registry->getOrigins())->toBe(['media' => 'App\\Models\\Media']);

    config([
        'example.scopes.registry' => [
            'origins' => ['category' => 'App\\Models\\Category'],
            'sources' => [],
        ],
    ]);

    expect($registry->getOrigins())->toBe(['media' => 'App\\Models\\Media']);

    $registry->flush();

    expect($registry->getOrigins())->toBe(['category' => 'App\\Models\\Category']);
});
