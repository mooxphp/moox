<?php

declare(strict_types=1);

namespace Moox\Invoice;

use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Core\MooxServiceProvider;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Models\InvoiceAllowanceCharge;
use Moox\Invoice\Models\InvoiceLine;
use Moox\Invoice\Support\InvoiceModels;
use Spatie\LaravelPackageTools\Package;

class InvoiceServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('invoice')
            ->hasConfigFile()
            ->hasMigrations([
                'create_invoices_table',
                'create_invoice_lines_table',
                'create_invoice_allowance_charges_table',
                'convert_delivery_json_to_party_shape',
                'add_document_versioning_to_invoices_table',
                'add_vat_category_to_invoices_table',
                'add_preceding_invoices_to_invoices_table',
                'add_supplier_number_to_invoices_table',
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

    public function packageBooted(): void
    {
        if (
            ! class_exists(AuditPackageRegistry::class)
            || ! config('audit.enabled', true)
            || ! config('invoice.audit.enabled', true)
        ) {
            return;
        }

        AuditPackageRegistry::register('invoice', $this->auditConfigForRegistry());
    }

    /**
     * Audit the configured model subclasses in place of the package models.
     *
     * @return array<string, mixed>
     */
    private function auditConfigForRegistry(): array
    {
        /** @var array<string, mixed> $audit */
        $audit = config('invoice.audit', []);
        $models = is_array($audit['models'] ?? null) ? $audit['models'] : [];

        $configuredModels = [
            Invoice::class => InvoiceModels::invoice(),
            InvoiceLine::class => InvoiceModels::invoiceLine(),
            InvoiceAllowanceCharge::class => InvoiceModels::invoiceAllowanceCharge(),
        ];

        foreach ($configuredModels as $packageModel => $configuredModel) {
            if ($packageModel === $configuredModel || ! isset($models[$packageModel])) {
                continue;
            }

            $models[$configuredModel] = $models[$packageModel];
            unset($models[$packageModel]);
        }

        $audit['models'] = $models;

        return $audit;
    }
}
