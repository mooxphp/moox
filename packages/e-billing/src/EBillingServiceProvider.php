<?php

declare(strict_types=1);

namespace Moox\EBilling;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Moox\Core\MooxServiceProvider;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Console\Commands\BackfillValidationScoresCommand;
use Moox\EBilling\Contracts\InvoiceParserInterface;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\FormatDefinition;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Formats\Strategies\ZugferdGeneratorStrategy;
use Moox\EBilling\Listeners\ProcessInboxAttachmentListener;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\EBilling;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\EBilling\Support\UnitCodeResolver;
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
        $this->app->singleton(DocumentTypeCodeResolver::class);
        $this->app->singleton(UnitCodeResolver::class);
        $this->app->singleton(ZugferdGeneratorStrategy::class);
        $this->registerFormatRegistry();

        $this->registerInvoiceParser();
    }

    private function registerFormatRegistry(): void
    {
        $this->app->singleton(FormatRegistry::class, function ($app): FormatRegistry {
            $registry = new FormatRegistry;
            $registry->register(new FormatDefinition(
                id: 'zugferd',
                label: 'ZUGFeRD',
                artifactKind: ArtifactKind::Pdf,
                profile: (string) config('zugferd.profile', 'EN16931'),
                strategy: $app->make(ZugferdGeneratorStrategy::class),
            ));

            return $registry;
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->registerInvoiceEbillingDocumentRelation();

        $this->registerEbillingDocumentConfigAlias();

        $this->registerZugferdFilesystemDisk();

        Event::listen(InboxAttachmentProcessed::class, ProcessInboxAttachmentListener::class);
    }

    /**
     * Bind the invoice parser from config. The package ships no parser — the PDF format
     * is host-specific — so a consumer sets `e-billing.parser` to an
     * {@see InvoiceParserInterface} implementation (e.g. in their host config). Left
     * unbound when not configured, so resolving {@see EBilling}
     * fails fast with a clear container error instead of silently using a wrong parser.
     */
    private function registerInvoiceParser(): void
    {
        $parser = config('e-billing.parser');

        if (! is_string($parser) || $parser === '') {
            return;
        }

        if (! is_a($parser, InvoiceParserInterface::class, true)) {
            throw new InvalidArgumentException(
                "config('e-billing.parser') must implement ".InvoiceParserInterface::class.": {$parser}"
            );
        }

        $this->app->bind(InvoiceParserInterface::class, $parser);
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
            ],
        ]);
    }
}
