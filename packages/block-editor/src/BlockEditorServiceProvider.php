<?php

declare(strict_types=1);

namespace Moox\BlockEditor;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class BlockEditorServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('block-editor')
            ->hasConfigFile()
            ->hasViews('block-editor')
            ->hasAssets()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Block Editor')
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

    public function boot(): void
    {
        parent::boot();

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
