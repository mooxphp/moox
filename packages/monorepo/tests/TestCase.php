<?php

namespace Moox\Monorepo\Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Moox\Monorepo\MonorepoServiceProvider;
use Orchestra\Testbench\Concerns\WithWorkbench;

abstract class TestCase extends BaseTestCase
{
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            MonorepoServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        // Define test environment setup
        $app['config']->set('monorepo.github.organization', 'test-org');
        $app['config']->set('monorepo.github.public_repo', 'test-repo');
        $app['config']->set('monorepo.cache.enabled', false);
    }
}
