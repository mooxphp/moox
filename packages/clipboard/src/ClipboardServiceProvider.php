<?php

declare(strict_types=1);

namespace Moox\Clipboard;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ClipboardServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('clipboard')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations()
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Clipboard')
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
                'Clipboard' => '%%PackageName%%',
                'clipboard' => '%%PackageSlug%%',
                'This is my package clipboard' => '%%Description%%',
                'building new Moox packages, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Clipboard' => '%%PackageName%%',
                'clipboard' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateRemove([
                'build.php',
            ]);
    }
}
