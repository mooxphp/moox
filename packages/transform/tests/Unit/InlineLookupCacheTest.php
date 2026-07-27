<?php

declare(strict_types=1);

use Moox\Transform\Support\Operations\InlineLookupCache;

test('inline lookup cache remembers values including null and can flush', function (): void {
    $cache = new InlineLookupCache;
    $calls = 0;

    $first = $cache->remember('missing', function () use (&$calls): ?int {
        $calls++;

        return null;
    });
    $second = $cache->remember('missing', function () use (&$calls): ?int {
        $calls++;

        return 99;
    });

    expect($first)->toBeNull()
        ->and($second)->toBeNull()
        ->and($calls)->toBe(1)
        ->and($cache->count())->toBe(1);

    $cache->flush();

    expect($cache->count())->toBe(0);

    $third = $cache->remember('missing', function () use (&$calls): int {
        $calls++;

        return 7;
    });

    expect($third)->toBe(7)->and($calls)->toBe(2);
});
