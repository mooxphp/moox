<?php

declare(strict_types=1);

namespace Moox\Item;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ItemServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('item')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Item')
            ->released(true)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'building new Moox packages, not used as installed package',
            ])
            ->templateFor([
                'we do not know yet',
            ])
            ->templateReplace([
                'Item' => '%%PackageName%%',
                'item' => '%%PackageSlug%%',
                'Item is a Moox Package using Moox Skeleton.' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',

            ])
            ->templateRename([
                'Item' => '%%PackageName%%',
                'item' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateRemove([
                'build.php',
            ])
            ->alternatePackages([
                'builder',
            ]);
    }
}
