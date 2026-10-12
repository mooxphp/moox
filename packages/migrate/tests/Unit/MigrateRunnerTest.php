<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Moox\Migrate\Support\MigrateOutput;
use Moox\Migrate\Support\MigrateRunner;

uses(RefreshDatabase::class);

it('truncates artisan output in the runner result', function (): void {
    $lines = [];

    for ($index = 1; $index <= 40; $index++) {
        $lines[] = 'line '.$index;
    }

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);
    Artisan::shouldReceive('output')->andReturn(implode("\n", $lines));

    $result = app(MigrateRunner::class)->run();

    expect($result['ok'])->toBeTrue()
        ->and($result['busy'])->toBeFalse()
        ->and($result['title'])->toBe(__('migrate::migrate.migrate_done'))
        ->and($result['body'])->toContain(MigrateOutput::TRUNCATED_MARKER)
        ->and($result['body'])->not->toContain("line 1\n");
});

it('puts only the exception message into the ui body', function (): void {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andThrow(new RuntimeException('connection refused'));

    $result = app(MigrateRunner::class)->run();

    expect($result['ok'])->toBeFalse()
        ->and($result['title'])->toBe(__('migrate::migrate.migrate_failed'))
        ->and($result['body'])->toBe('connection refused');
});

it('rejects a second run while the lock is held', function (): void {
    $lock = Cache::lock(MigrateRunner::LOCK_KEY, 60);
    expect($lock->get())->toBeTrue();

    try {
        $result = app(MigrateRunner::class)->run();

        expect($result['busy'])->toBeTrue()
            ->and($result['ok'])->toBeFalse()
            ->and($result['title'])->toBe(__('migrate::migrate.migrate_busy'));
    } finally {
        $lock->release();
    }
});
