<?php

declare(strict_types=1);

use Moox\Cache\Support\ArtisanCacheTarget;
use Moox\Cache\Support\CacheTargetRegistry;

it('registers and groups cache targets', function (): void {
    $registry = new CacheTargetRegistry;

    $registry->register(ArtisanCacheTarget::make(
        key: 'application-cache',
        label: 'Application cache',
        command: 'cache:clear',
        category: 'laravel',
    ));

    $registry->register(ArtisanCacheTarget::make(
        key: 'optimize-clear',
        label: 'Optimize clear',
        command: 'optimize:clear',
        category: 'laravel',
    ));

    expect($registry->all())->toHaveCount(2)
        ->and($registry->groupedByCategory())->toHaveKey('laravel')
        ->and($registry->get('application-cache'))->not->toBeNull();
});
