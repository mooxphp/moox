<?php

declare(strict_types=1);

namespace Moox\Person;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class PersonServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('person')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_persons_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Person')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Person records belonging to a contact',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating person entities',
            ])
            ->templateReplace([
                'Person' => '%%PackageName%%',
                'person' => '%%PackageSlug%%',
                'Person is a Moox Entity for people belonging to a contact.' => '%%Description%%',
                'Person records belonging to a contact' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Person' => '%%PackageName%%',
                'person' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/person.php',
                'database/migrations/create_persons_table.php.stub',
                'resources/lang/en/person.php',
                'resources/lang/en/fields.php',
                'src/Models/Person.php',
                'src/Resources/Person/Pages/CreatePerson.php',
                'src/Resources/Person/Pages/EditPerson.php',
                'src/Resources/Person/Pages/ListPersons.php',
                'src/Resources/Person/Pages/ViewPerson.php',
                'src/Resources/PersonResource.php',
                'src/Support/PersonRules.php',
                'src/Plugins/PersonPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
