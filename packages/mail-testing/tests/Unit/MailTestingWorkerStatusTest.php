<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Moox\MailTesting\Support\MailTestingWorkerStatus;
use Tests\TestCase;

uses(TestCase::class);

it('builds the worker and render commands', function (): void {
    expect(MailTestingWorkerStatus::workerCommand())
        ->toBe('php artisan queue:work --queue=mail-testing --tries=1 --timeout=0')
        ->and(MailTestingWorkerStatus::renderCommand([
            'count' => 100,
            'engine' => 'php',
            'persist_backend' => 'storage',
            'validation_level' => 'soft',
            'minify' => true,
        ]))->toBe('php artisan mail-testing:render --count=100 --engine=php --persist=storage --validation=soft --minify');
});

it('detects queue:work command lines that listen on mail-testing', function (): void {
    expect(MailTestingWorkerStatus::commandListensToQueue(
        'php artisan queue:work --queue=mail-testing --tries=1',
        'mail-testing',
    ))->toBeTrue()
        ->and(MailTestingWorkerStatus::commandListensToQueue(
            'php artisan queue:work',
            'mail-testing',
        ))->toBeFalse()
        ->and(MailTestingWorkerStatus::commandListensToQueue(
            'php artisan queue:listen --queue=default,mail-testing',
            'mail-testing',
        ))->toBeTrue();
});

it('treats a fresh heartbeat as an active worker when the queue is not sync', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();

    expect(MailTestingWorkerStatus::isActive())->toBeFalse()
        ->and(MailTestingWorkerStatus::shouldQueue())->toBeFalse();

    MailTestingWorkerStatus::rememberIfListening('mail-testing');

    expect(MailTestingWorkerStatus::isActive())->toBeTrue()
        ->and(MailTestingWorkerStatus::shouldQueue())->toBeTrue();

    MailTestingWorkerStatus::rememberIfListening('default');

    expect(MailTestingWorkerStatus::isActive())->toBeTrue();
});
