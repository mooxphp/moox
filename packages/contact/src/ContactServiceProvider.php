<?php

declare(strict_types=1);

namespace Moox\Contact;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class ContactServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('contact')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_contacts_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Contact')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Contact records',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating contact entities',
            ])
            ->templateReplace([
                'Contact' => '%%PackageName%%',
                'contact' => '%%PackageSlug%%',
                'Contact records.' => '%%Description%%',
                'Contact records' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Contact' => '%%PackageName%%',
                'contact' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/contact.php',
                'database/factories/ContactFactory.php',
                'database/migrations/create_contacts_table.php.stub',
                'resources/lang/en/contact.php',
                'resources/lang/en/fields.php',
                'src/Models/Contact.php',
                'src/Resources/Contact/Pages/CreateContact.php',
                'src/Resources/Contact/Pages/EditContact.php',
                'src/Resources/Contact/Pages/ListContacts.php',
                'src/Resources/Contact/Pages/ViewContact.php',
                'src/Resources/ContactResource.php',
                'src/Support/ContactRules.php',
                'src/Plugins/ContactPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
