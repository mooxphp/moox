<?php

declare(strict_types=1);

namespace Moox\Audit\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Moox\Audit\AuditServiceProvider;
use Moox\Audit\Models\Activity;
use Moox\Audit\Support\AuditBootstrap;
use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Audit\Tests\Support\TestAuditableItem;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\Activitylog\ActivitylogServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        AuditBootstrap::clear();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ActivitylogServiceProvider::class,
            AuditServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('audit.enabled', true);
        $app['config']->set('audit.activity_model', Activity::class);
        $app['config']->set('activitylog.enabled', true);
        $app['config']->set('activitylog.activity_model', Activity::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        $auditMigration = include dirname(__DIR__).'/database/migrations/create_activity_log_table.php.stub';
        $auditMigration->up();

        Schema::create('test_auditable_items', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->string('status')->default('draft');
            $table->string('scope')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    protected function registerTestAuditableModel(): string
    {
        $modelClass = TestAuditableItem::class;

        AuditPackageRegistry::register('test', [
            'models' => [
                $modelClass => [
                    'enabled' => true,
                    'log_name' => 'test',
                    'entry_type' => 'audit',
                    'attributes' => ['title', 'status', 'scope'],
                    'events' => ['created', 'updated', 'deleted', 'restored'],
                ],
            ],
        ]);

        AuditBootstrap::boot();

        return $modelClass;
    }

    protected function assertActivityLogged(string $event, ?string $logName = 'test'): Activity
    {
        $activity = Activity::query()
            ->where('event', $event)
            ->when($logName !== null, fn ($query) => $query->where('log_name', $logName))
            ->first();

        expect($activity)->not->toBeNull();

        return $activity;
    }
}
