<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Moox\Cache\Data\CacheClearRequest;
use Moox\Cache\Support\ArtisanCacheTarget;

it('clears cache via artisan command', function (): void {
    Artisan::shouldReceive('call')
        ->once()
        ->with('cache:clear')
        ->andReturn(0);

    Artisan::shouldReceive('output')
        ->once()
        ->andReturn('Application cache cleared.');

    $target = ArtisanCacheTarget::make(
        key: 'application-cache',
        label: 'Application cache',
        command: 'cache:clear',
    );

    $result = $target->clear(new CacheClearRequest);

    expect($result->success)->toBeTrue()
        ->and($result->output)->toBe('Application cache cleared.');
});
