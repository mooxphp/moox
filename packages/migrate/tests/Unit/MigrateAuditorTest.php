<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Moox\Audit\Models\Activity;
use Moox\Migrate\Support\MigrateAuditor;

uses(RefreshDatabase::class);

it('is a no-op when moox audit is unavailable or disabled', function (): void {
    config(['migrate.audit.enabled' => false]);

    $user = User::factory()->create([
        'email' => 'audit-disabled@heco.de',
    ]);

    expect(fn () => MigrateAuditor::log($user, true, false, 0, 2))
        ->not->toThrow(Throwable::class);
});

it('logs through MooxActivityLogger when audit is available', function (): void {
    if (! class_exists('Moox\\Audit\\Services\\MooxActivityLogger')) {
        $this->markTestSkipped('moox/audit is not installed');
    }

    if (! function_exists('activity')) {
        $this->markTestSkipped('spatie activity helper is not available');
    }

    config([
        'migrate.audit.enabled' => true,
        'migrate.audit.log_name' => 'migrate',
    ]);

    $user = User::factory()->create([
        'email' => 'audit-enabled@heco.de',
    ]);

    MigrateAuditor::log($user, false, false, 1, 3);

    $activity = Activity::query()
        ->where('log_name', 'migrate')
        ->where('event', 'migrate_run')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->entry_type)->toBe('log')
        ->and($activity->causer_id)->toEqual($user->getKey())
        ->and($activity->properties->get('status'))->toBe('fail')
        ->and($activity->properties->get('exit_code'))->toBe(1)
        ->and($activity->properties->get('pending_count'))->toBe(3);
});
