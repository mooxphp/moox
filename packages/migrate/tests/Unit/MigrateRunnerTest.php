<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Moox\Migrate\Jobs\RunMigrationsJob;
use Moox\Migrate\Support\MigrateOutput;
use Moox\Migrate\Support\MigrateRunner;
use Moox\Migrate\Support\MigrateRunStatus;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    MigrateRunStatus::clear();
});

it('truncates artisan output in the execute result', function (): void {
    $lines = [];

    for ($index = 1; $index <= 40; $index++) {
        $lines[] = 'line '.$index;
    }

    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andReturn(0);
    Artisan::shouldReceive('output')->andReturn(implode("\n", $lines));

    $result = app(MigrateRunner::class)->execute();

    expect($result['ok'])->toBeTrue()
        ->and($result['busy'])->toBeFalse()
        ->and($result['title'])->toBe(__('migrate::migrate.migrate_done'))
        ->and($result['body'])->toContain(MigrateOutput::TRUNCATED_MARKER)
        ->and($result['body'])->not->toContain("line 1\n")
        ->and($result['exit_code'])->toBe(0);
});

it('puts only the exception message into the ui body', function (): void {
    Artisan::shouldReceive('call')
        ->once()
        ->with('migrate', ['--force' => true])
        ->andThrow(new RuntimeException('connection refused'));

    $result = app(MigrateRunner::class)->execute();

    expect($result['ok'])->toBeFalse()
        ->and($result['title'])->toBe(__('migrate::migrate.migrate_failed'))
        ->and($result['body'])->toBe('connection refused')
        ->and($result['exit_code'])->toBeNull();
});

it('rejects execute while the lock is held', function (): void {
    $lock = Cache::lock(MigrateRunner::LOCK_KEY, 60);
    expect($lock->get())->toBeTrue();

    try {
        $result = app(MigrateRunner::class)->execute();

        expect($result['busy'])->toBeTrue()
            ->and($result['ok'])->toBeFalse()
            ->and($result['title'])->toBe(__('migrate::migrate.migrate_busy'));
    } finally {
        $lock->release();
    }
});

it('dispatches a queue job from queue()', function (): void {
    Queue::fake();

    $result = app(MigrateRunner::class)->queue();

    expect($result['queued'])->toBeTrue()
        ->and($result['terminal'])->toBeFalse()
        ->and($result['title'])->toBe(__('migrate::migrate.migrate_queued'))
        ->and(MigrateRunStatus::isActive())->toBeTrue();

    Queue::assertPushed(RunMigrationsJob::class);
});

it('rejects queue while a run is already active', function (): void {
    Queue::fake();

    MigrateRunStatus::put([
        'state' => MigrateRunStatus::STATE_RUNNING,
        'title' => __('migrate::migrate.migrate_running'),
        'body' => null,
        'exit_code' => null,
        'pending_count' => 1,
        'user_id' => null,
        'user_type' => null,
    ]);

    $result = app(MigrateRunner::class)->queue();

    expect($result['busy'])->toBeTrue()
        ->and($result['queued'])->toBeFalse()
        ->and($result['title'])->toBe(__('migrate::migrate.migrate_busy'));

    Queue::assertNothingPushed();
});
