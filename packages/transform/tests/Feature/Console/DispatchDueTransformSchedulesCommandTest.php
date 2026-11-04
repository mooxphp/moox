<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Moox\Transform\Jobs\RunScheduledTransformDefinitionJob;
use Tests\TestCase;

require_once dirname(__DIR__, 2).'/Support/TransformTestSupport.php';

uses(TestCase::class);

beforeEach(function (): void {
    Carbon::setTestNow('2026-11-03 02:00:00');
    createTestTables();
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('it queues only active definitions whose schedule is due', function (): void {
    Bus::fake();

    createDefinition([
        'name' => 'due',
        'schedule' => '0 2 * * *',
    ]);
    createDefinition([
        'name' => 'later',
        'schedule' => '30 3 * * *',
    ]);
    createDefinition([
        'name' => 'manual',
        'schedule' => null,
    ]);
    createDefinition([
        'name' => 'inactive',
        'schedule' => '* * * * *',
        'is_active' => false,
    ]);

    $this->artisan('transform:dispatch-due')
        ->expectsOutput('Queued 1 transform run(s).')
        ->assertSuccessful();

    Bus::assertDispatchedTimes(RunScheduledTransformDefinitionJob::class, 1);
});

test('it rejects a schedule that is not a cron expression', function (): void {
    createDefinition([
        'name' => 'broken',
        'schedule' => 'not a cron',
    ]);
})->throws(ValidationException::class);
