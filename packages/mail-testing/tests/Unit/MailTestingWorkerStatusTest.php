<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Moox\MailTesting\Support\MailTestingWorkerStatus;
use Tests\TestCase;

uses(TestCase::class);

it('builds the worker and render commands', function (): void {
    expect(MailTestingWorkerStatus::workerCommand())
        ->toBe('php artisan queue:work --queue=mail-testing,default --tries=1 --timeout=0')
        ->and(MailTestingWorkerStatus::renderCommand([
            'count' => 100,
            'engine' => 'php',
            'persist_backend' => 'storage',
            'validation_level' => 'soft',
            'minify' => true,
            'source_template_slug' => 'login',
            'source_template_locale' => 'de_DE',
        ]))->toBe('php artisan mail-testing:render --count=100 --engine=php --persist=storage --template=login --locale=de_DE --validation=soft --minify');
});

it('detects queue:work command lines that listen on mail-testing or default', function (): void {
    expect(MailTestingWorkerStatus::commandListensToQueue(
        'php artisan queue:work --queue=mail-testing --tries=1',
        'mail-testing',
    ))->toBeTrue()
        ->and(MailTestingWorkerStatus::commandListensToQueue(
            'php artisan queue:work',
            'mail-testing',
        ))->toBeFalse()
        ->and(MailTestingWorkerStatus::commandListensToQueue(
            'php artisan queue:work',
            'default',
        ))->toBeTrue()
        ->and(MailTestingWorkerStatus::commandListensToQueue(
            'php artisan queue:work --queue=default',
            'default',
        ))->toBeTrue()
        ->and(MailTestingWorkerStatus::commandListensToQueue(
            'php artisan queue:listen --queue=default,mail-testing',
            'mail-testing',
        ))->toBeTrue();
});

it('treats a fresh mail-testing heartbeat as an active worker when the queue is not sync', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();

    expect(MailTestingWorkerStatus::isActive())->toBeFalse()
        ->and(MailTestingWorkerStatus::shouldQueue())->toBeFalse()
        ->and(MailTestingWorkerStatus::targetQueue())->toBe('mail-testing');

    MailTestingWorkerStatus::rememberIfListening('mail-testing');

    expect(MailTestingWorkerStatus::isActive())->toBeTrue()
        ->and(MailTestingWorkerStatus::shouldQueue())->toBeTrue()
        ->and(MailTestingWorkerStatus::targetQueue())->toBe('mail-testing');
});

it('treats a fresh default heartbeat as an active worker when the queue is not sync', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();

    expect(MailTestingWorkerStatus::isActive())->toBeFalse();

    MailTestingWorkerStatus::rememberIfListening('default');

    expect(MailTestingWorkerStatus::isActive())->toBeTrue()
        ->and(MailTestingWorkerStatus::shouldQueue())->toBeTrue()
        ->and(MailTestingWorkerStatus::targetQueue())->toBe('default');
});

it('prefers the configured queue when both heartbeats are fresh', function (): void {
    config(['queue.default' => 'database']);
    Cache::flush();
    MailTestingWorkerStatus::rememberIfListening('default');
    MailTestingWorkerStatus::rememberIfListening('mail-testing');

    expect(MailTestingWorkerStatus::targetQueue())->toBe('mail-testing');
});

it('does not treat the sync driver as an active worker', function (): void {
    config(['queue.default' => 'sync']);
    Cache::flush();

    expect(MailTestingWorkerStatus::usesQueue())->toBeFalse()
        ->and(MailTestingWorkerStatus::isActive())->toBeFalse()
        ->and(MailTestingWorkerStatus::shouldQueue())->toBeFalse()
        ->and(MailTestingWorkerStatus::targetQueue())->toBe('mail-testing');
});
