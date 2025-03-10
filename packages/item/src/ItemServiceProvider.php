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
            ->templateReplace([
                'Item' => '%%PackageName%%',
                'item' => '%%PackageSlug%%',
                'Item is a simple Moox Entity, that can be used to create and manage simple entries, like logs.' => '%%Description%%',
                'building a simple Moox Entity, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Item' => '%%PackageName%%',
                'item' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/item.php',
                'database/factories/ItemFactory.php',
                'database/migrations/create_items_table.php.stub',
                'resources/lang/en/item.php',
                'src/Moox/Item/ItemPlugin.php',
                'src/Models/Item.php',
                'src/Resources/ItemResource.php',
                'src/Resources/ItemResource\Pages\CreateItem.php',
                'src/Resources/PublishItemResource\Pages\EditPublishItem.php',
                'src/Resources/PublishItemResource\Pages\ListPublishItems.php',
                'src/Resources/PublishItemResource\Pages\ViewPublishItem.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
