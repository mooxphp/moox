<?php

declare(strict_types=1);

use Moox\Connect\Support\ConnectQueueSettingsResolver;

beforeEach(function (): void {
    ConnectQueueSettingsResolver::clearCache();

    config()->set('connect.jobs.detail_list', [
        'queue' => 'connect-detail-list',
        'tries' => 7,
        'timeout' => 420,
    ]);
});

test('resolves queue settings from job type config', function (): void {
    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list');

    expect($settings->queue)->toBe('connect-detail-list')
        ->and($settings->tries)->toBe(7)
        ->and($settings->timeout)->toBe(420);
});

test('config endpoint map overrides job type queue name', function (): void {
    config()->set('connect.queues.endpoints', [
        '6' => ['queue' => 'articles-de', 'tries' => 9],
    ]);

    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list', 6);

    expect($settings->queue)->toBe('articles-de')
        ->and($settings->tries)->toBe(9)
        ->and($settings->timeout)->toBe(420);
});

test('config connection map is used when endpoint has no queue override', function (): void {
    config()->set('connect.queues.connections', [
        '1' => ['queue' => 'comwork-sync', 'timeout' => 600],
    ]);

    $settings = app(ConnectQueueSettingsResolver::class)->resolve('detail_list', null, 1);

    expect($settings->queue)->toBe('comwork-sync')
        ->and($settings->timeout)->toBe(600)
        ->and($settings->tries)->toBe(7);
});
