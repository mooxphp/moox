<?php

declare(strict_types=1);

namespace Moox\Supplier;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class SupplierServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('supplier')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_suppliers_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Supplier')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Supplier records belonging to a contact',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating supplier entities',
            ])
            ->templateReplace([
                'Supplier' => '%%PackageName%%',
                'supplier' => '%%PackageSlug%%',
                'Supplier is a Moox Entity for supplier profiles belonging to a contact.' => '%%Description%%',
                'Supplier records belonging to a contact' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Supplier' => '%%PackageName%%',
                'supplier' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/supplier.php',
                'database/migrations/create_suppliers_table.php.stub',
                'resources/lang/en/supplier.php',
                'resources/lang/en/fields.php',
                'src/Models/Supplier.php',
                'src/Resources/Supplier/Pages/CreateSupplier.php',
                'src/Resources/Supplier/Pages/EditSupplier.php',
                'src/Resources/Supplier/Pages/ListSuppliers.php',
                'src/Resources/Supplier/Pages/ViewSupplier.php',
                'src/Resources/SupplierResource.php',
                'src/Support/SupplierRules.php',
                'src/Plugins/SupplierPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
