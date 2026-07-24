<?php

declare(strict_types=1);

use Moox\Connect\Support\ConnectQueueSettingsResolver;

beforeEach(function (): void {
    ConnectQueueSettingsResolver::clearCache();

    config()->set('connect.jobs.detail_list', [
        'queue' => 'connect-detail-list',
        'tries' => 7,
        'timeout' => 420,
        'max_exceptions' => 4,
        'backoff' => [10, 20],
        'retry_until_minutes' => 120,
        'overlap' => [
            'release_after' => 5,
            'expire_buffer' => 30,
            'expire_min' => 90,
        ],
        'deadlock_retry' => [
            'attempts' => 2,
            'delays_ms' => [50, 150],
        ],
    ]);
});

test('resolves queue settings from job type config', function (): void {
    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list');

    expect($settings->queue)->toBe('connect-detail-list')
        ->and($settings->tries)->toBe(7)
        ->and($settings->timeout)->toBe(420)
        ->and($settings->maxExceptions)->toBe(4)
        ->and($settings->backoff)->toBe([10, 20])
        ->and($settings->retryUntilMinutes)->toBe(120)
        ->and($settings->overlapReleaseAfter)->toBe(5)
        ->and($settings->overlapExpireBuffer)->toBe(30)
        ->and($settings->overlapExpireMin)->toBe(90)
        ->and($settings->overlapExpireAfter())->toBe(450)
        ->and($settings->deadlockRetryAttempts)->toBe(2)
        ->and($settings->deadlockRetryDelaysMs)->toBe([50, 150]);
});

test('config endpoint map overrides job type queue name', function (): void {
    config()->set('connect.queues.endpoints', [
        '6' => [
            'queue' => 'articles-de',
            'tries' => 9,
            'retry_until_minutes' => 0,
            'backoff' => '60,180',
        ],
    ]);

    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list', 6);

    expect($settings->queue)->toBe('articles-de')
        ->and($settings->tries)->toBe(9)
        ->and($settings->timeout)->toBe(420)
        ->and($settings->retryUntilMinutes)->toBe(0)
        ->and($settings->retryUntil())->toBeNull()
        ->and($settings->backoff)->toBe([60, 180]);
});

test('config connection map is used when endpoint has no queue override', function (): void {
    config()->set('connect.queues.connections', [
        '1' => [
            'queue' => 'comwork-sync',
            'timeout' => 600,
            'max_exceptions' => 8,
        ],
    ]);

    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list', null, 1);

    expect($settings->queue)->toBe('comwork-sync')
        ->and($settings->timeout)->toBe(600)
        ->and($settings->tries)->toBe(7)
        ->and($settings->maxExceptions)->toBe(8);
});

test('retry until minutes of zero disables retry until window', function (): void {
    config()->set('connect.jobs.detail_list.retry_until_minutes', 0);

    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list');

    expect($settings->retryUntilMinutes)->toBe(0)
        ->and($settings->retryUntil())->toBeNull();
});
