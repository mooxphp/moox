<?php

declare(strict_types=1);

namespace Moox\Address;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class AddressServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('address')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_addresses_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Address')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Address records with polymorphic owner',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating address entities',
            ])
            ->templateReplace([
                'Address' => '%%PackageName%%',
                'address' => '%%PackageSlug%%',
                'Address is a Moox Entity for addresses with polymorphic owner.' => '%%Description%%',
                'Address records with polymorphic owner' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Address' => '%%PackageName%%',
                'address' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/address.php',
                'database/migrations/create_addresses_table.php.stub',
                'resources/lang/en/address.php',
                'resources/lang/en/fields.php',
                'src/Models/Address.php',
                'src/Resources/Address/Pages/CreateAddress.php',
                'src/Resources/Address/Pages/EditAddress.php',
                'src/Resources/Address/Pages/ListAddresses.php',
                'src/Resources/Address/Pages/ViewAddress.php',
                'src/Resources/AddressResource.php',
                'src/Support/AddressRules.php',
                'src/Plugins/AddressPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
