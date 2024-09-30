<?php

namespace Moox\Builder\Tests;

use Workbench\App\Models\User;
use Orchestra\Testbench\TestCase as Orchestra;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends Orchestra
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->actingAs(User::factory()->create());
    }

    protected function getEnvironmentSetUp($app) {}
}
