<?php

declare(strict_types=1);

namespace Moox\Demo\Seeding;

use Illuminate\Database\Seeder;

final class RunsMooxDemoAssets
{
    public static function invoke(Seeder $seeder): void
    {
        if (! DemoAssetGate::enabled()) {
            return;
        }

        if (! method_exists($seeder, 'seedDemoAssets')) {
            return;
        }

        $method = new \ReflectionMethod($seeder, 'seedDemoAssets');
        $method->setAccessible(true);
        $method->invoke($seeder);
    }
}
