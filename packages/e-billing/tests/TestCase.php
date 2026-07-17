<?php

declare(strict_types=1);

namespace Moox\EBilling\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as AppTestCase;

class TestCase extends AppTestCase
{
    use RefreshDatabase;

    protected function seedDocumentTypeAndUnitCodelists(): void
    {
        $this->artisan('moox:data:import-codelists', ['scheme' => 'untdid1001'])->assertSuccessful();
        $this->artisan('moox:data:import-codelists', ['scheme' => 'rec20'])->assertSuccessful();
    }
}
