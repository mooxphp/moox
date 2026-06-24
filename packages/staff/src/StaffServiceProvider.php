<?php

declare(strict_types=1);

namespace Moox\Staff;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class StaffServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('staff')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_staff_table',
                'create_staff_assignments_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Staff')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'ERP staff records for internal employees (legacy Bearbeiter)',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating ERP-style staff entities with legacy data overflow',
            ])
            ->templateReplace([
                'Staff' => '%%PackageName%%',
                'staff' => '%%PackageSlug%%',
                'Staff is a Moox Entity for internal employees (legacy Bearbeiter).' => '%%Description%%',
                'ERP staff records for internal employees (legacy Bearbeiter)' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Staff' => '%%PackageName%%',
                'staff' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/staff.php',
                'database/factories/StaffFactory.php',
                'database/migrations/create_staff_table.php.stub',
                'resources/lang/en/staff.php',
                'resources/lang/en/fields.php',
                'src/Models/Staff.php',
                'src/Resources/Staff/Pages/CreateStaff.php',
                'src/Resources/Staff/Pages/EditStaff.php',
                'src/Resources/Staff/Pages/ListStaff.php',
                'src/Resources/Staff/Pages/ViewStaff.php',
                'src/Resources/StaffResource.php',
                'src/Support/StaffRules.php',
                'src/Plugins/StaffPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
