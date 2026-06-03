<?php

declare(strict_types=1);

namespace Moox\Company;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class CompanyServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('company')
            ->hasConfigFile()
            ->hasTranslations()
            ->hasMigrations([
                'create_companies_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Company')
            ->released(false)
            ->stability('stable')
            ->category('development')
            ->usedFor([
                'ERP company records (customers, suppliers, subsidiaries)',
            ])
            ->alternatePackages([
                '',
            ])
            ->templateFor([
                'creating ERP-style company entities with pivot-based addresses',
            ])
            ->templateReplace([
                'Company' => '%%PackageName%%',
                'company' => '%%PackageSlug%%',
                'Company is a Moox Entity for ERP-style company records (customers, suppliers, subsidiaries) without payment or default-address FKs.' => '%%Description%%',
                'ERP company records (customers, suppliers, subsidiaries)' => '%%UsedFor%%',
                'released(true)' => 'released(false)',
                'stability(stable)' => 'stability(dev)',
                'category(development)' => 'category(unknown)',
                'moox/builder' => '',
            ])
            ->templateRename([
                'Company' => '%%PackageName%%',
                'company' => '%%PackageSlug%%',
            ])
            ->templateSectionReplace([
                "/<!--shortdesc-->.*<!--\/shortdesc-->/s" => '%%Description%%',
            ])
            ->templateEntityFiles([
                'config/company.php',
                'database/factories/CompanyFactory.php',
                'database/migrations/create_companies_table.php.stub',
                'resources/lang/en/company.php',
                'resources/lang/en/fields.php',
                'src/Models/Company.php',
                'src/Frontend/CompanyFrontend.php',
                'src/Resources/Company/Pages/CreateCompany.php',
                'src/Resources/Company/Pages/EditCompany.php',
                'src/Resources/Company/Pages/ListCompanies.php',
                'src/Resources/Company/Pages/ViewCompany.php',
                'src/Resources/CompanyResource.php',
                'src/Support/CompanyRules.php',
                'src/Plugins/CompanyPlugin.php',
                'resources/lang/de/fields.php',
            ])
            ->templateRemove([
                '',
            ]);
    }
}
