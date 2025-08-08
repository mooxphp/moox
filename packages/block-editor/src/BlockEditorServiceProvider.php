<?php

declare(strict_types=1);

namespace Moox\Skeleton;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class SkeletonServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('skeleton')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Skeleton')
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
                'creating simple Laravel packages',
            ])
            ->templateReplace([
                'Skeleton' => '%%PackageName%%',
                'skeleton' => '%%PackageSlug%%',
                'This template is used for generating Laravel packages, all Moox packages are built with this template.' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Skeleton' => '%%PackageName%%',
                'skeleton' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateRemove([
                'build.php',
            ]);
    }
}
