<?php

declare(strict_types=1);

namespace Moox\Record;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class RecordServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('record')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations('create_records_table')
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Record')
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
                'Record' => '%%PackageName%%',
                'record' => '%%PackageSlug%%',
                'Record is a simple Moox Entity, that can be used to create and manage simple entries, like logs.' => '%%Description%%',
                'building a simple Moox Entity, not used as installed package' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Record' => '%%PackageName%%',
                'record' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/record.php',
                'database/factories/RecordFactory.php',
                'database/migrations/create_records_table.php.stub',
                'resources/lang/en/record.php',
                'src/Models/Record.php',
                'src/Moox/Entities/Records/Record/Frontend/RecordFrontend.php',
                'src/Moox/Entities/Records/Record/Pages/CreateRecord.php',
                'src/Moox/Entities/Records/Record/Pages/EditRecord.php',
                'src/Moox/Entities/Records/Record/Pages/ListRecords.php',
                'src/Moox/Entities/Records/Record/Pages/ViewRecord.php',
                'src/Moox/Entities/Records/Record/RecordResource.php',
                'src/Moox/Plugins/RecordPlugin.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
