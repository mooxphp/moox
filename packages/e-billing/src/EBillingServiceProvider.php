<?php

declare(strict_types=1);

namespace Moox\EBilling;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Event;
use Moox\Core\MooxServiceProvider;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Console\Commands\BackfillValidationScoresCommand;
use Moox\EBilling\Listeners\ProcessInboxAttachmentListener;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\Invoice\Models\Invoice;
use Moox\MailInbox\Events\InboxAttachmentProcessed;
use Spatie\LaravelPackageTools\Package;

class EBillingServiceProvider extends MooxServiceProvider
{
    public function configureMoox(Package $package): void
    {
        $package
            ->name('e-billing')
            ->hasConfigFile()
            ->hasViews()
            ->hasTranslations()
            ->hasRoutes('web')
            ->hasCommands([
                BackfillValidationScoresCommand::class,
            ])
            ->hasMigrations([
                'create_ebilling_documents_table',
            ]);

        $this->getMooxPackage()
            ->title('Moox eBilling')
            ->released(false)
            ->stability('dev')
            ->category('billing')
            ->usedFor([
                'extracting invoice data from PDFs and converting to e-invoices',
            ]);
    }

    public function packageRegistered(): void
    {
        parent::packageRegistered();

        $this->app->singleton(InvoiceFieldValidator::class);
        $this->app->singleton(ConfirmInvoiceAction::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Bind InvoiceParserInterface in your host app ServiceProvider:
        // $this->app->bind(\Moox\EBilling\Contracts\InvoiceParserInterface::class, YourParser::class);

        $this->registerInvoiceEbillingDocumentRelation();

        $this->registerEbillingDocumentConfigAlias();

        $this->registerZugferdFilesystemDisk();

        Event::listen(InboxAttachmentProcessed::class, ProcessInboxAttachmentListener::class);
    }

    private function registerInvoiceEbillingDocumentRelation(): void
    {
        Invoice::resolveRelationUsing('ebillingDocument', function (Invoice $invoice): HasOne {
            return $invoice->hasOne(EbillingDocument::class, 'invoice_id');
        });
    }

    /**
     * {@see EbillingDocument::getResourceName()} reads config under `ebilling-document`.
     */
    private function registerEbillingDocumentConfigAlias(): void
    {
        $config = config('e-billing');

        if (is_array($config)) {
            config(['ebilling-document' => $config]);
        }
    }

    private function registerZugferdFilesystemDisk(): void
    {
        $configuredRoot = config('e-billing.zugferd.storage_root');
        $root = is_string($configuredRoot) && $configuredRoot !== ''
            ? $configuredRoot
            : storage_path('app/private/'.trim((string) config('mail-inbox.zugferd.path', 'zugferd'), '/'));

        config([
            'filesystems.disks.zugferd' => [
                'driver' => 'local',
                'root' => $root,
                'serve' => true,
                'throw' => false,
                'report' => false,
            ],
        ]);
    }
}
