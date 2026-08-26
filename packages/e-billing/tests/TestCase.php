<?php

declare(strict_types=1);

namespace Moox\EBilling\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase as AppTestCase;

class TestCase extends AppTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTestingUsesIsolatedDatabase();

        config([
            'mail-inbox.graph.tenant_id' => 'test-tenant',
            'mail-inbox.graph.client_id' => 'test-client',
            'mail-inbox.graph.client_secret' => 'test-secret',
        ]);

        $this->ensureEbillingMorphRelationsConfig();
        $this->runVeraPdfMigrations();
        $this->runActivityLogMigration();
    }

    /**
     * Guard against RefreshDatabase wiping a real MySQL database when phpunit.xml
     * env overrides are missing (e.g. ad-hoc pest runs against the host app).
     */
    private function assertTestingUsesIsolatedDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        $isSqliteMemory = $connection === 'sqlite' && in_array($database, [':memory:', ''], true);
        $isSqliteFile = $connection === 'sqlite' && str_contains($database, 'testing');

        if ($isSqliteMemory || $isSqliteFile) {
            return;
        }

        throw new \RuntimeException(
            "Refusing to run e-billing tests against non-isolated DB [{$connection}:{$database}]. ".
            'Use packages/e-billing/phpunit.xml (sqlite :memory:) or set DB_CONNECTION=sqlite DB_DATABASE=:memory:.'
        );
    }

    public function seedDocumentTypeAndUnitCodelists(): void
    {
        $this->artisan('moox:data:import-codelists', ['scheme' => 'untdid1001'])->assertSuccessful();
        $this->artisan('moox:data:import-codelists', ['scheme' => 'rec20'])->assertSuccessful();
    }

    private function ensureEbillingMorphRelationsConfig(): void
    {
        /** @var array<string, mixed> $packageConfig */
        $packageConfig = require dirname(__DIR__).'/config/e-billing.php';

        config([
            'e-billing' => array_replace_recursive(
                is_array(config('e-billing')) ? config('e-billing') : [],
                $packageConfig,
            ),
            'ebilling-document' => array_replace_recursive(
                is_array(config('ebilling-document')) ? config('ebilling-document') : [],
                $packageConfig,
            ),
        ]);
    }

    private function runVeraPdfMigrations(): void
    {
        $verapdfMigrationsPath = dirname(__DIR__, 2).'/verapdf/database/migrations';

        if (! Schema::hasTable('verapdf_validations')) {
            $validations = include $verapdfMigrationsPath.'/create_verapdf_validations_table.php.stub';
            $validations->up();
        }

        if (! Schema::hasTable('verapdf_validatables')) {
            $validatables = include $verapdfMigrationsPath.'/create_verapdf_validatables_table.php.stub';
            $validatables->up();
        }

        if (! Schema::hasTable('ebilling_uploaded_pdf_sources')) {
            $uploadedSources = include dirname(__DIR__).'/database/migrations/create_ebilling_uploaded_pdf_sources_table.php.stub';
            $uploadedSources->up();
        }
    }

    private function runActivityLogMigration(): void
    {
        if (Schema::hasTable('activity_log')) {
            return;
        }

        $stub = dirname(__DIR__, 2).'/audit/database/migrations/create_activity_log_table.php.stub';

        if (! is_file($stub)) {
            return;
        }

        $migration = include $stub;
        $migration->up();
    }
}
