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

        $this->ensureEbillingMorphRelationsConfig();
        $this->runVeraPdfMigrations();
    }

    protected function seedDocumentTypeAndUnitCodelists(): void
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
        if (! Schema::hasTable('verapdf_validations')) {
            $validations = include dirname(__DIR__, 2).'/verapdf/database/migrations/create_verapdf_validations_table.php.stub';
            $validations->up();
        }

        if (! Schema::hasTable('verapdf_validatables')) {
            $validatables = include dirname(__DIR__, 2).'/verapdf/database/migrations/create_verapdf_validatables_table.php.stub';
            $validatables->up();
        }
    }
}
