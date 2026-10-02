<?php

declare(strict_types=1);

namespace Moox\Pdf\Tests;

use Moox\Pdf\PdfServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            PdfServiceProvider::class,
        ];
    }
}
