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
                'src/Models/Item.php',
                'src/Moox/Entities/Items/Item/Frontend/ItemFrontend.php',
                'src/Moox/Entities/Items/Item/Pages/CreateItem.php',
                'src/Moox/Entities/Items/Item/Pages/EditItem.php',
                'src/Moox/Entities/Items/Item/Pages/ListItems.php',
                'src/Moox/Entities/Items/Item/Pages/ViewItem.php',
                'src/Moox/Entities/Items/Item/ItemResource.php',
                'src/Moox/Plugins/ItemPlugin.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
