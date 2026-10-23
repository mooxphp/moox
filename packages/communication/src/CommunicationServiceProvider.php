<?php

declare(strict_types=1);

namespace Moox\Communication;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CommunicationServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('communication')
            ->hasConfigFile(['communication', 'communication-type', 'communication-rule'])
            ->hasTranslations()
            ->hasMigrations([
                'create_communication_types_table',
                'create_communication_type_translations_table',
                'create_communications_table',
                'create_communication_rules_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Communication')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Communication records with polymorphic owner',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating communication entities',
            ])
            ->templateReplace([
                'Communication' => '%%PackageName%%',
                'communication' => '%%PackageSlug%%',
                'Communication is a Moox Entity for communication channels with polymorphic owner.' => '%%Description%%',
                'Communication records with polymorphic owner' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Communication' => '%%PackageName%%',
                'communication' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/communication.php',
                'database/migrations/create_communications_table.php.stub',
                'resources/lang/en/communication.php',
                'resources/lang/en/fields.php',
                'src/Models/Communication.php',
                'src/Resources/Communication/Pages/CreateCommunication.php',
                'src/Resources/Communication/Pages/EditCommunication.php',
                'src/Resources/Communication/Pages/ListCommunications.php',
                'src/Resources/Communication/Pages/ViewCommunication.php',
                'src/Resources/CommunicationResource.php',
                'src/Plugins/CommunicationPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
