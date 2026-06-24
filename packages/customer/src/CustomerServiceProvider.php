<?php

declare(strict_types=1);

namespace Moox\Customer;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CustomerServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('customer')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_customers_table',
                'create_customer_assignments_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Customer')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'ERP customer profiles linked to company master data',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating ERP-style customer entities with configurable company relation',
            ])
            ->templateReplace([
                'Customer' => '%%PackageName%%',
                'customer' => '%%PackageSlug%%',
                'Customer is a Moox Entity for ERP customer profiles linked to companies.' => '%%Description%%',
                'ERP customer profiles linked to company master data' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Customer' => '%%PackageName%%',
                'customer' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/customer.php',
                'database/factories/CustomerFactory.php',
                'database/migrations/create_customers_table.php.stub',
                'database/migrations/create_customer_assignments_table.php.stub',
                'resources/lang/en/customer.php',
                'resources/lang/en/fields.php',
                'src/Models/Customer.php',
                'src/Models/CustomerAssignment.php',
                'src/Resources/Customer/Pages/CreateCustomer.php',
                'src/Resources/Customer/Pages/EditCustomer.php',
                'src/Resources/Customer/Pages/ListCustomers.php',
                'src/Resources/Customer/Pages/ViewCustomer.php',
                'src/Resources/CustomerResource.php',
                'src/Support/CustomerRules.php',
                'src/Plugins/CustomerPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
