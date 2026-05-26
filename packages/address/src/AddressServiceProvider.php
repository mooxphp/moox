<?php

declare(strict_types=1);

namespace Moox\Address;

use Moox\Address\Models\Address;
use Moox\Address\Resources\AddressResource;
use Moox\Core\MooxServiceProvider;
use Moox\Core\Support\MorphPivot\MorphPivotRelationRegistry;
use Spatie\LaravelPackageTools\Package;

class AddressServiceProvider extends MooxServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        MorphPivotRelationRegistry::registerRelatedModel(Address::class, [
            'display_columns' => ['name', 'city', 'postal_code', 'country_code', 'is_primary'],
            'translation_prefix' => 'address::fields',
            'related_resource' => AddressResource::class,
            'record_select_label' => 'formattedLine',
            'record_select_search_columns' => ['name', 'city', 'postal_code', 'street', 'street2', 'label'],
        ]);
    }

    public function configureMoox(Package $package): void
    {
        $package
            ->name('address')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_addresses_table',
                'create_addressables_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Address')
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
                'Address' => '%%PackageName%%',
                'address' => '%%PackageSlug%%',
                'Address is a simple Moox Entity, that can be used to create and manage addresses.' => '%%Description%%',
                'building a simple Moox Entity, not used as installed package' => '%%UsedFor%%',
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
                'database/factories/AddressFactory.php',
                'database/migrations/create_addresses_table.php.stub',
                'database/migrations/create_addressables_table.php.stub',
                'src/Models/Addressable.php',
                'src/Concerns/HasAddresses.php',
                'resources/lang/en/address.php',
                'src/Models/Address.php',
                'src/Frontend/AddressFrontend.php',
                'src/Resources/Address/Pages/CreateAddress.php',
                'src/Resources/Address/Pages/EditAddress.php',
                'src/Resources/Address/Pages/ListAddresses.php',
                'src/Resources/Address/Pages/ViewAddress.php',
                'src/Resources/AddressResource.php',
                'src/Resources/Address/RelationManagers/AddressablesRelationManager.php',
                'src/Support/AddressRelationConfig.php',
                'src/Plugins/AddressPlugin.php',
                'resources/lang/en/fields.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
