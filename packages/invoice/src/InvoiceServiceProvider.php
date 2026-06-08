<?php

declare(strict_types=1);

namespace Moox\Invoice;

use Moox\Core\MooxServiceProvider;
use Spatie\LaravelPackageTools\Package;

class InvoiceServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('invoice')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasMigrations([
                'create_invoices_table',
                'create_invoice_lines_table',
                'create_invoice_allowance_charges_table',
            ])
            ->hasCommands();

        $this->getMooxPackage()
            ->title('Moox Invoice')
            ->released(true)
            ->stability('dev')
            ->category('development')
            ->usedFor([
                'representing structured invoices with lines, allowances and charges',
            ]);
    }
}
