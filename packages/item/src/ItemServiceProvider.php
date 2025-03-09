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
                'Item is a Moox Entity, Laravel Model and Filament Resource' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                '\'moox/builder\',' => '// optional alternative package (e.g. moox/builder)',
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
                'moox/builder',
            ]);
    }
}
