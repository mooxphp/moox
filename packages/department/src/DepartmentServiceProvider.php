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
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_departments_table',
                'create_department_assignments_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Department')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'ERP department records assignable to companies and contacts',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating ERP-style department entities with pivot-based assignments',
            ])
            ->templateReplace([
                'Department' => '%%PackageName%%',
                'department' => '%%PackageSlug%%',
                'Department is a Moox Entity for organizational units assignable to companies and contacts.' => '%%Description%%',
                'ERP department records assignable to companies and contacts' => '%%UsedFor%%',
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
                'database/factories/DepartmentFactory.php',
                'database/migrations/create_departments_table.php.stub',
                'database/migrations/create_department_assignments_table.php.stub',
                'resources/lang/en/department.php',
                'resources/lang/en/fields.php',
                'src/Models/Department.php',
                'src/Resources/Department/Pages/CreateDepartment.php',
                'src/Resources/Department/Pages/EditDepartment.php',
                'src/Resources/Department/Pages/ListDepartments.php',
                'src/Resources/Department/Pages/ViewDepartment.php',
                'src/Resources/DepartmentResource.php',
                'src/Support/DepartmentRules.php',
                'src/Plugins/DepartmentPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
