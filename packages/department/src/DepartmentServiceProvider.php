<?php

declare(strict_types=1);

namespace Moox\Department;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class DepartmentServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('department')
            ->hasConfigFile(['department', 'department-person'])
            ->hasTranslations()
            ->hasMigrations([
                'create_departments_table',
                'create_department_persons_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Department')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'Department records belonging to a contact',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating department entities',
            ])
            ->templateReplace([
                'Department' => '%%PackageName%%',
                'department' => '%%PackageSlug%%',
                'Department is a Moox Entity for organizational units belonging to a contact.' => '%%Description%%',
                'Department records belonging to a contact' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Department' => '%%PackageName%%',
                'department' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/department.php',
                'config/department-person.php',
                'database/factories/DepartmentFactory.php',
                'database/migrations/create_departments_table.php.stub',
                'database/migrations/create_department_persons_table.php.stub',
                'resources/lang/en/department.php',
                'resources/lang/en/fields.php',
                'src/Models/Department.php',
                'src/Models/DepartmentPerson.php',
                'src/Resources/Department/Pages/CreateDepartment.php',
                'src/Resources/Department/Pages/EditDepartment.php',
                'src/Resources/Department/Pages/ListDepartments.php',
                'src/Resources/Department/Pages/ViewDepartment.php',
                'src/Resources/DepartmentPerson/Pages/CreateDepartmentPerson.php',
                'src/Resources/DepartmentPerson/Pages/EditDepartmentPerson.php',
                'src/Resources/DepartmentPerson/Pages/ListDepartmentPersons.php',
                'src/Resources/DepartmentPerson/Pages/ViewDepartmentPerson.php',
                'src/Resources/DepartmentResource.php',
                'src/Resources/DepartmentPersonResource.php',
                'src/Support/DepartmentRules.php',
                'src/Plugins/DepartmentPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
