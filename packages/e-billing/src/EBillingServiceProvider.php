<?php

declare(strict_types=1);

namespace Moox\EBilling;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Core\MooxServiceProvider;
use Moox\EBilling\Actions\ApproveDocumentAction;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Actions\CreateManualUploadDocumentAction;
use Moox\EBilling\Actions\DispatchDocumentAction;
use Moox\EBilling\Actions\InitializeDocumentApprovalAction;
use Moox\EBilling\Actions\InvalidateDocumentApprovalAction;
use Moox\EBilling\Actions\RecordApprovalTransitionAction;
use Moox\EBilling\Actions\RejectDocumentAction;
use Moox\EBilling\Actions\ReleaseSeverityFieldAction;
use Moox\EBilling\Actions\RematchAttributionAction;
use Moox\EBilling\Actions\RestoreRejectedDocumentAction;
use Moox\EBilling\Actions\SetInvoiceAttributionAction;
use Moox\EBilling\Actions\TryAutoApproveDocumentAction;
use Moox\EBilling\Approval\AutoApproveEvaluator;
use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Approval\DocumentDispatchGuard;
use Moox\EBilling\Console\Commands\BackfillValidationScoresCommand;
use Moox\EBilling\Contracts\InvoiceParserInterface;
use Moox\EBilling\Contracts\PdfaNormalizerInterface;
use Moox\EBilling\Contracts\SourcePdfPreparerInterface;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\FormatDefinition;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Formats\Strategies\ZugferdGeneratorStrategy;
use Moox\EBilling\Listeners\ProcessInboxAttachmentListener;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\EBilling;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\EBilling\Support\LetterheadSourcePdfPreparer;
use Moox\EBilling\Support\PassthroughPdfaNormalizer;
use Moox\EBilling\Support\UnitCodeResolver;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;
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
                'alter_ebilling_documents_source_id_to_string',
                'add_copy_pdf_storage_path_to_ebilling_documents_table',
                'add_source_content_hash_to_ebilling_documents_table',
                'add_severity_releases_to_ebilling_documents_table',
                'add_approval_state_to_ebilling_documents_table',
                'create_ebilling_uploaded_pdf_sources_table',
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
        $this->app->singleton(CreateManualUploadDocumentAction::class);
        $this->app->singleton(SetInvoiceAttributionAction::class);
        $this->app->singleton(RematchAttributionAction::class);
        $this->app->singleton(ReleaseSeverityFieldAction::class);
        $this->app->singleton(AutoApproveEvaluator::class);
        $this->app->singleton(DocumentApprovalGuard::class);
        $this->app->singleton(DocumentDispatchGuard::class);
        $this->app->singleton(RecordApprovalTransitionAction::class);
        $this->app->singleton(ApproveDocumentAction::class);
        $this->app->singleton(RejectDocumentAction::class);
        $this->app->singleton(RestoreRejectedDocumentAction::class);
        $this->app->singleton(TryAutoApproveDocumentAction::class);
        $this->app->singleton(InitializeDocumentApprovalAction::class);
        $this->app->singleton(DispatchDocumentAction::class);
        $this->app->singleton(InvalidateDocumentApprovalAction::class);

        $this->app->singleton(DocumentTypeCodeResolver::class);
        $this->app->singleton(UnitCodeResolver::class);
        $this->app->singleton(ZugferdGeneratorStrategy::class);

        if (! $this->app->bound(SourcePdfPreparerInterface::class)) {
            $this->app->bind(SourcePdfPreparerInterface::class, LetterheadSourcePdfPreparer::class);
        }

        if (! $this->app->bound(PdfaNormalizerInterface::class)) {
            $this->app->bind(PdfaNormalizerInterface::class, PassthroughPdfaNormalizer::class);
        }
        $this->registerFormatRegistry();

        $this->registerInvoiceParser();
    }

    private function registerFormatRegistry(): void
    {
        $this->app->singleton(FormatRegistry::class, function ($app): FormatRegistry {
            $registry = new FormatRegistry;
            $strategy = $app->make(ZugferdGeneratorStrategy::class);

            $registry->register(new FormatDefinition(
                id: 'xrechnung',
                label: 'XRechnung',
                artifactKind: ArtifactKind::Xml,
                profile: 'XRECHNUNG',
                strategy: $strategy,
            ));

            $registry->register(new FormatDefinition(
                id: 'zugferd',
                label: 'ZUGFeRD',
                artifactKind: ArtifactKind::Pdf,
                profile: (string) config('zugferd.profile', 'EN16931'),
                strategy: $strategy,
            ));

            $registry->register(new FormatDefinition(
                id: 'factur-x',
                label: 'Factur-X',
                artifactKind: ArtifactKind::Pdf,
                profile: (string) config('zugferd.profile', 'EN16931'),
                strategy: $strategy,
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

    public function packageBooted(): void
    {
        if (! class_exists(AuditPackageRegistry::class) || ! config('audit.enabled', true)) {
            return;
        }

        AuditPackageRegistry::register('e-billing', $this->auditConfigForRegistry());
    }

    /**
     * Resolve host invoice model subclasses into the audit registry keys.
     *
     * @return array<string, mixed>
     */
    private function auditConfigForRegistry(): array
    {
        /** @var array<string, mixed> $audit */
        $audit = config('e-billing.audit', []);
        $invoiceClass = InvoiceModels::invoice();

        if ($invoiceClass === Invoice::class) {
            return $audit;
        }

        $models = is_array($audit['models'] ?? null) ? $audit['models'] : [];

        if (isset($models[Invoice::class]) && is_array($models[Invoice::class])) {
            $models[$invoiceClass] = $models[Invoice::class];
            unset($models[Invoice::class]);
            $audit['models'] = $models;
        }

        $filament = is_array($audit['filament'] ?? null) ? $audit['filament'] : [];

        foreach ($filament as $resourceClass => $resourceConfig) {
            if (! is_array($resourceConfig)) {
                continue;
            }

            if (($resourceConfig['owner_model'] ?? null) === Invoice::class) {
                $filament[$resourceClass]['owner_model'] = $invoiceClass;
            }
        }

        $audit['filament'] = $filament;

        return $audit;
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
