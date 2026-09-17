<?php

declare(strict_types=1);

namespace Moox\EBilling;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Moox\Audit\Support\AuditPackageRegistry;
use Moox\Core\MooxServiceProvider;
use Moox\Core\Services\RelationService;
use Moox\EBilling\Actions\AnnounceDocumentNeedsReviewAction;
use Moox\EBilling\Actions\ApproveDocumentAction;
use Moox\EBilling\Actions\ConfirmInvoiceAction;
use Moox\EBilling\Actions\CreateManualUploadDocumentAction;
use Moox\EBilling\Actions\DispatchDocumentAction;
use Moox\EBilling\Actions\InitializeDocumentApprovalAction;
use Moox\EBilling\Actions\InvalidateDocumentApprovalAction;
use Moox\EBilling\Actions\QueueDocumentDeliveryAction;
use Moox\EBilling\Actions\RecordApprovalTransitionAction;
use Moox\EBilling\Actions\RecordDeliveryAttemptsAction;
use Moox\EBilling\Actions\RejectDocumentAction;
use Moox\EBilling\Actions\ReleaseSeverityFieldAction;
use Moox\EBilling\Actions\RematchAttributionAction;
use Moox\EBilling\Actions\RestoreRejectedDocumentAction;
use Moox\EBilling\Actions\SetInvoiceAttributionAction;
use Moox\EBilling\Actions\TryAutoApproveDocumentAction;
use Moox\EBilling\Approval\AutoApproveEvaluator;
use Moox\EBilling\Approval\BatchedReviewNotificationStrategy;
use Moox\EBilling\Approval\DocumentApprovalGuard;
use Moox\EBilling\Approval\DocumentDispatchGuard;
use Moox\EBilling\Approval\ImmediateReviewNotificationStrategy;
use Moox\EBilling\Console\Commands\BackfillValidationScoresCommand;
use Moox\EBilling\Console\Commands\FlushReviewNotificationBatchCommand;
use Moox\EBilling\Console\Commands\ScanOverdueApprovalEscalationCommand;
use Moox\EBilling\Contracts\DeliveryRecipientResolverInterface;
use Moox\EBilling\Contracts\InvoiceParserInterface;
use Moox\EBilling\Contracts\PdfaNormalizerInterface;
use Moox\EBilling\Contracts\RecipientFormatPreferenceResolverInterface;
use Moox\EBilling\Contracts\ReviewNotificationRecorderInterface;
use Moox\EBilling\Contracts\ReviewNotificationStrategyInterface;
use Moox\EBilling\Contracts\SourcePdfPreparerInterface;
use Moox\EBilling\Delivery\ConfigurableDeliveryRecipientResolver;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Formats\FormatDefinition;
use Moox\EBilling\Formats\FormatRegistry;
use Moox\EBilling\Formats\Strategies\ZugferdGeneratorStrategy;
use Moox\EBilling\Listeners\ProcessInboxAttachmentListener;
use Moox\EBilling\Models\EbillingDeliveryAttempt;
use Moox\EBilling\Models\EbillingDocument;
use Moox\EBilling\Services\InvoiceFieldValidator;
use Moox\EBilling\Support\AllowedProfiles;
use Moox\EBilling\Support\CustomerFormatPreferenceResolver;
use Moox\EBilling\Support\DocumentTypeCodeResolver;
use Moox\EBilling\Support\InvoiceRelationsConfig;
use Moox\EBilling\Support\LetterheadSourcePdfPreparer;
use Moox\EBilling\Support\NullReviewNotificationRecorder;
use Moox\EBilling\Support\PassthroughPdfaNormalizer;
use Moox\EBilling\Support\UnitCodeResolver;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;
use Moox\KositValidator\Models\KositValidation;
use Moox\MailInbox\Events\InboxAttachmentProcessed;
use Moox\MailOutbox\Models\MailSendLog;
use Moox\VeraPdf\Models\VeraPdfValidation;
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
                FlushReviewNotificationBatchCommand::class,
                ScanOverdueApprovalEscalationCommand::class,
            ])
            ->hasMigrations([
                'create_ebilling_documents_table',
                'alter_ebilling_documents_source_id_to_string',
                'add_copy_pdf_storage_path_to_ebilling_documents_table',
                'add_source_content_hash_to_ebilling_documents_table',
                'add_severity_releases_to_ebilling_documents_table',
                'add_approval_state_to_ebilling_documents_table',
                'create_ebilling_uploaded_pdf_sources_table',
                'add_profile_to_ebilling_documents_table',
                'create_ebilling_delivery_attempts_table',
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
        $this->app->singleton(QueueDocumentDeliveryAction::class);
        $this->app->singleton(RecordDeliveryAttemptsAction::class);
        $this->app->singleton(AnnounceDocumentNeedsReviewAction::class);
        $this->app->singleton(InvalidateDocumentApprovalAction::class);

        $this->registerReviewNotificationRecorder();

        $this->app->bind(ReviewNotificationStrategyInterface::class, function ($app): ReviewNotificationStrategyInterface {
            $strategy = (string) config('e-billing.notification.strategy', 'immediate');

            return match ($strategy) {
                'batched' => $app->make(BatchedReviewNotificationStrategy::class),
                default => $app->make(ImmediateReviewNotificationStrategy::class),
            };
        });

        $this->app->singleton(DocumentTypeCodeResolver::class);
        $this->app->singleton(UnitCodeResolver::class);
        $this->app->singleton(ZugferdGeneratorStrategy::class);

        if (! $this->app->bound(SourcePdfPreparerInterface::class)) {
            $this->app->bind(SourcePdfPreparerInterface::class, LetterheadSourcePdfPreparer::class);
        }

        if (! $this->app->bound(RecipientFormatPreferenceResolverInterface::class)) {
            $this->app->bind(RecipientFormatPreferenceResolverInterface::class, CustomerFormatPreferenceResolver::class);
        }

        if (! $this->app->bound(PdfaNormalizerInterface::class)) {
            $this->app->bind(PdfaNormalizerInterface::class, PassthroughPdfaNormalizer::class);
        }

        if (! $this->app->bound(DeliveryRecipientResolverInterface::class)) {
            $this->app->bind(DeliveryRecipientResolverInterface::class, ConfigurableDeliveryRecipientResolver::class);
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

            $hybridProfile = AllowedProfiles::hybridDefault();

            $registry->register(new FormatDefinition(
                id: 'zugferd',
                label: 'ZUGFeRD',
                artifactKind: ArtifactKind::Pdf,
                profile: $hybridProfile,
                strategy: $strategy,
            ));

            $registry->register(new FormatDefinition(
                id: 'factur-x',
                label: 'Factur-X',
                artifactKind: ArtifactKind::Pdf,
                profile: $hybridProfile,
                strategy: $strategy,
            ));

            return $registry;
        });
    }

    public function boot(): void
    {
        parent::boot();

        InvoiceRelationsConfig::mergeIntoInvoiceRelations();
        $this->registerInvoiceEbillingDocumentRelation();
        $this->registerInvoiceDeliveryAttemptsRelation();
        $this->registerInvoiceKositValidationsRelation();
        $this->registerInvoiceVeraPdfValidationsRelation();
        $this->registerInvoiceMailSendLogsRelation();
        $this->registerKositValidatableOwnerTypes();
        $this->registerVeraPdfValidatableOwnerTypes();
        $this->forgetRelationServiceInstance();

        $this->registerEbillingDocumentConfigAlias();

        $this->registerZugferdFilesystemDisk();

        Event::listen(InboxAttachmentProcessed::class, ProcessInboxAttachmentListener::class);
    }

    public function packageBooted(): void
    {
        if (
            ! class_exists(AuditPackageRegistry::class)
            || ! config('audit.enabled', true)
            || ! config('e-billing.audit.enabled', true)
        ) {
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
     * Bind the review-notification recorder from config. Default is a no-op.
     * Hosts set `e-billing.notification.recorder` to a
     * {@see ReviewNotificationRecorderInterface} implementation (same pattern as `parser`).
     */
    private function registerReviewNotificationRecorder(): void
    {
        $this->app->bind(ReviewNotificationRecorderInterface::class, NullReviewNotificationRecorder::class);

        $recorder = config('e-billing.notification.recorder');

        if (! is_string($recorder) || $recorder === '') {
            return;
        }

        if (! is_a($recorder, ReviewNotificationRecorderInterface::class, true)) {
            throw new InvalidArgumentException(
                "config('e-billing.notification.recorder') must implement ".ReviewNotificationRecorderInterface::class.": {$recorder}"
            );
        }

        $this->app->bind(ReviewNotificationRecorderInterface::class, $recorder);
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

    private function registerInvoiceDeliveryAttemptsRelation(): void
    {
        Invoice::resolveRelationUsing('deliveryAttempts', function (Invoice $invoice): HasManyThrough {
            return $invoice->hasManyThrough(
                EbillingDeliveryAttempt::class,
                EbillingDocument::class,
                'invoice_id',
                'ebilling_document_id',
                $invoice->getKeyName(),
                'id',
            );
        });
    }

    /**
     * Expose document-owned KoSIT validations on the invoice (read-only Filament tabs).
     */
    private function registerInvoiceKositValidationsRelation(): void
    {
        if (! class_exists(KositValidation::class)) {
            return;
        }

        Invoice::resolveRelationUsing('kositValidations', function (Invoice $invoice): MorphToMany {
            $document = EbillingDocument::query()->where('invoice_id', $invoice->getKey())->first();

            if (! $document instanceof EbillingDocument) {
                return (new EbillingDocument)->kositValidations()->whereRaw('0 = 1');
            }

            return $document->kositValidations();
        });
    }

    /**
     * Expose document-owned veraPDF validations on the invoice (read-only Filament tabs).
     */
    private function registerInvoiceVeraPdfValidationsRelation(): void
    {
        if (! class_exists(VeraPdfValidation::class)) {
            return;
        }

        Invoice::resolveRelationUsing('veraPdfValidations', function (Invoice $invoice): MorphToMany {
            $document = EbillingDocument::query()->where('invoice_id', $invoice->getKey())->first();

            if (! $document instanceof EbillingDocument) {
                return (new EbillingDocument)->veraPdfValidations()->whereRaw('0 = 1');
            }

            return $document->veraPdfValidations();
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

    private function registerInvoiceMailSendLogsRelation(): void
    {
        if (! class_exists(MailSendLog::class)) {
            return;
        }

        Invoice::resolveRelationUsing('mailSendLogs', function (Invoice $invoice) {
            return $invoice->morphMany(MailSendLog::class, 'related');
        });
    }

    /**
     * Register EbillingDocument as an allowed KoSIT morph owner (assignments tab).
     */
    private function registerKositValidatableOwnerTypes(): void
    {
        if (! config()->has('kosit-validator.relations.kosit_validatables')) {
            return;
        }

        $existing = config('kosit-validator.relations.kosit_validatables.owner_types', []);

        config([
            'kosit-validator.relations.kosit_validatables.owner_types' => array_replace(
                is_array($existing) ? $existing : [],
                [
                    EbillingDocument::class => [
                        'label' => __('e-billing::ebilling.ebilling_document'),
                    ],
                ],
            ),
        ]);
    }

    /**
     * Register EbillingDocument as an allowed veraPDF morph owner (assignments tab).
     */
    private function registerVeraPdfValidatableOwnerTypes(): void
    {
        if (! config()->has('verapdf.relations.verapdf_validatables')) {
            return;
        }

        $existing = config('verapdf.relations.verapdf_validatables.owner_types', []);

        config([
            'verapdf.relations.verapdf_validatables.owner_types' => array_replace(
                is_array($existing) ? $existing : [],
                [
                    EbillingDocument::class => [
                        'label' => __('e-billing::ebilling.ebilling_document'),
                    ],
                ],
            ),
        ]);
    }

    private function forgetRelationServiceInstance(): void
    {
        if ($this->app->bound(RelationService::class)) {
            $this->app->forgetInstance(RelationService::class);
        }
    }
}
