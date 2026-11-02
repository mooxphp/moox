<?php

declare(strict_types=1);

namespace Moox\Support;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class SupportServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package->name('support');

        $this->getMooxPackage()
            ->title('Moox Support')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Shared model and form traits',
            ]);
    }
}
