<?php

declare(strict_types=1);

namespace Moox\Mjml\Tests;

use Moox\Mjml\MjmlServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            MjmlServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('mjml.use_php_renderer', true);
    }
}
