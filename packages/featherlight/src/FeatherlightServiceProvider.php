<?php

declare(strict_types=1);

namespace Moox\Featherlight;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class FeatherlightServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('featherlight')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Featherlight')
            ->released(true)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'building new Moox packages, not used as installed package',
            ])
            ->alternatePackages([
                'moox/builder', // optional alternative package (e.g. moox/post)
            ])
            ->templateFor([
                'we do not know yet',
            ])
            ->templateReplace([
                'Featherlight' => '%%PackageName%%',
                'featherlight' => '%%PackageSlug%%',
                'This is my package featherlight' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Featherlight' => '%%PackageName%%',
                'featherlight' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateRemove([
                'build.php',
            ]);
    }
}
