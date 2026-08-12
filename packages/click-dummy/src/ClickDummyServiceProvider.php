<?php

declare(strict_types=1);

namespace Moox\ClickDummy;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ClickDummyServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('click-dummy')
            ->hasConfigFile()
            ->hasRoutes(['web']);
    }
}
