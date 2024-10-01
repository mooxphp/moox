<?php

namespace Moox\Builder\Tests;

use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as Orchestra;
use Workbench\App\Models\User;

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
