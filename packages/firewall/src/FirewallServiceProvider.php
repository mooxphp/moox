<?php

declare(strict_types=1);

namespace Moox\Firewall;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FirewallServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('firewall')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Firewall')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                '%%UsedFor%%',
            ])
            ->alternatePackages([
                '', // optional alternative package (e.g. moox/post)
            ])
            ->templateFor([
                'creating simple Laravel packages',
            ])
            
            
            
            ->templateRemove([
                'build.php',
            ]);
    }
}
