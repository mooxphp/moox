<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Moox\Audit\Models\Activity;
use Moox\Audit\Tests\TestCase;

uses(TestCase::class);

it('reports affected entries without deleting them during dry run', function (): void {
    Carbon::setTestNow('2026-07-28 12:00:00');

    config()->set('audit.retention', [
        'log' => 7,
        'audit' => 30,
    ]);

    Activity::query()->create([
        'log_name' => 'system',
        'entry_type' => 'log',
        'description' => 'old log',
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);

    $this->artisan('mooxaudit:prune', ['--dry-run' => true])
        ->expectsOutputToContain('Would prune [log]')
        ->expectsOutputToContain('Dry run complete.')
        ->assertSuccessful();

    expect(Activity::query()->count())->toBe(1);
});

it('prunes entries older than the configured retention per entry type', function (): void {
    Carbon::setTestNow('2026-07-28 12:00:00');

    config()->set('audit.retention', [
        'log' => 7,
        'audit' => 30,
    ]);

    Activity::query()->create([
        'log_name' => 'system',
        'entry_type' => 'log',
        'description' => 'old log',
        'created_at' => now()->subDays(8),
        'updated_at' => now()->subDays(8),
    ]);

    Activity::query()->create([
        'log_name' => 'system',
        'entry_type' => 'log',
        'description' => 'fresh log',
        'created_at' => now()->subDays(6),
        'updated_at' => now()->subDays(6),
    ]);

    Activity::query()->create([
        'log_name' => 'audit',
        'entry_type' => 'audit',
        'description' => 'old audit',
        'created_at' => now()->subDays(31),
        'updated_at' => now()->subDays(31),
    ]);

    Activity::query()->create([
        'log_name' => 'audit',
        'entry_type' => 'audit',
        'description' => 'fresh audit',
        'created_at' => now()->subDays(29),
        'updated_at' => now()->subDays(29),
    ]);

    Activity::query()->create([
        'log_name' => 'audit',
        'entry_type' => 'audit',
        'description' => 'keep forever',
        'created_at' => now()->subDays(365),
        'updated_at' => now()->subDays(365),
    ]);

    config()->set('audit.retention.audit', null);

    $this->artisan('mooxaudit:prune')
        ->expectsOutputToContain('Pruned [log]')
        ->expectsOutputToContain('Skipping [audit]: keep indefinitely.')
        ->expectsOutputToContain('Prune complete.')
        ->assertSuccessful();

    expect(Activity::query()->pluck('description')->all())
        ->toBe([
            'fresh log',
            'old audit',
            'fresh audit',
            'keep forever',
        ]);
});

it('warns when no retention rules are configured', function (): void {
    config()->set('audit.retention', []);

    $this->artisan('mooxaudit:prune')
        ->expectsOutputToContain('No audit.retention rules configured.')
        ->assertSuccessful();
});
