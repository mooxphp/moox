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
                'create_supplier_assignments_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Supplier')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'ERP supplier profiles linked to company master data',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating ERP-style supplier entities with configurable company relation',
            ])
            ->templateReplace([
                'Supplier' => '%%PackageName%%',
                'supplier' => '%%PackageSlug%%',
                'Supplier is a Moox Entity for ERP supplier profiles linked to companies.' => '%%Description%%',
                'ERP supplier profiles linked to company master data' => '%%UsedFor%%',
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
                'database/factories/SupplierFactory.php',
                'database/migrations/create_suppliers_table.php.stub',
                'database/migrations/create_supplier_assignments_table.php.stub',
                'resources/lang/en/supplier.php',
                'resources/lang/en/fields.php',
                'src/Models/Supplier.php',
                'src/Models/SupplierAssignment.php',
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
