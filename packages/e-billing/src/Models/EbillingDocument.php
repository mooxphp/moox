<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Moox\Company\Models\Company;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Core\Traits\MorphPivot\HasMorphPivotRelations;
use Moox\Customer\Models\Customer;
use Moox\EBilling\Enums\AttributionSource;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\EBilling\Support\EBillingArtifactNaming;
use Moox\Invoice\Models\Invoice;
use Moox\Invoice\Support\InvoiceModels;
use Moox\KositValidator\Models\KositValidation;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\VeraPdf\Models\VeraPdfValidation;
use RuntimeException;

/**
 * Temporary duplication: review/score methods below mirror legacy {@see Invoice}
 * until slice D redirects the pipeline and removes them from Invoice.
 *
 * Line-level field validations remain on {@see InvoiceLine} until slices B/C; {@see fieldValidationItems()}
 * does not aggregate lines on the document yet.
 *
 * @property array<string, mixed>|null $bill_data
 * @property string|null $xml_storage_path
 * @property string|null $storage_disk
 * @property string|null $pdf_storage_path
 * @property string|null $copy_pdf_storage_path
 * @property string $format
 * @property string|null $artifact_content_hash
 * @property string|null $source_content_hash SHA-256 of the source PDF bytes (identity for identical-content duplicates).
 * @property array<string, mixed>|null $ignored_reason
 * @property EBillingAttachmentProcessingStatus|null $gateway_status
 * @property InvoiceProcessingStatus|null $review_status
 * @property array<string, mixed>|null $field_validations
 * @property array<string, mixed>|null $severity_releases
 * @property string|null $invoice_id
 * @property string|null $customer_id Identity of the document (matched customer). Gate visibility on this, resolved live.
 * @property string|null $company_id Reporting only — derived from the matched customer; never an access boundary.
 * @property AttributionSource|null $attribution_source How customer_id was set (`auto` matcher vs `manual` operator). Manual survives rematch.
 * @property int|null $validation_score
 * @property string|null $scope
 */
class EbillingDocument extends BaseItemModel
{
    use HasMorphPivotRelations;
    use HasUuids;

    protected $table = 'ebilling_documents';

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'format' => 'zugferd',
    ];

    /**
     * severity_releases is intentionally omitted: only ReleaseSeverityFieldAction writes it.
     *
     * @var list<string>
     */
    protected $fillable = [
        'source_type',
        'source_id',
        'bill_data',
        'xml_storage_path',
        'storage_disk',
        'pdf_storage_path',
        'copy_pdf_storage_path',
        'format',
        'artifact_content_hash',
        'source_content_hash',
        'ignored_reason',
        'gateway_status',
        'review_status',
        'validation_score',
        'field_validations',
        'processed_at',
        'error_message',
        'invoice_id',
        'company_id',
        'customer_id',
        'attribution_source',
        'scope',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bill_data' => 'array',
            'ignored_reason' => 'array',
            'gateway_status' => EBillingAttachmentProcessingStatus::class,
            'review_status' => InvoiceProcessingStatus::class,
            'attribution_source' => AttributionSource::class,
            'field_validations' => 'array',
            // severity_releases is intentionally omitted from $fillable: only ReleaseSeverityFieldAction
            // may write it. Bulk update([...]) silently drops keys not in $fillable.
            'severity_releases' => 'array',
            'validation_score' => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public static function getResourceName(): string
    {
        return 'ebilling-document';
    }

    public static function forSourceAttachment(InboxAttachment $attachment): ?self
    {
        return self::query()
            ->where('source_type', $attachment->getMorphClass())
            ->where('source_id', (string) $attachment->getKey())
            ->first();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function inboxAttachment(): ?InboxAttachment
    {
        $source = $this->source;

        return $source instanceof InboxAttachment ? $source : null;
    }

    public function sourceFullPath(): string
    {
        $source = $this->source;

        if ($source instanceof InboxAttachment) {
            return $source->fullPath();
        }

        if ($source instanceof UploadedPdfSource) {
            return $source->sourceFullPath();
        }

        throw new RuntimeException('Ebilling document has no resolvable source PDF path.');
    }

    public function sourceOriginalFilename(): string
    {
        $source = $this->source;

        if ($source instanceof InboxAttachment) {
            return (string) ($source->filename ?? 'document.pdf');
        }

        if ($source instanceof UploadedPdfSource) {
            return (string) ($source->original_filename ?: basename($source->source_pdf_path));
        }

        return 'document.pdf';
    }

    public function sourceStorageDisk(): ?string
    {
        $source = $this->source;

        if ($source instanceof InboxAttachment) {
            return $source->storage_disk ?? (string) config('mail-inbox.attachments.disk', 'local');
        }

        if ($source instanceof UploadedPdfSource) {
            return $source->source_pdf_disk;
        }

        return null;
    }

    public function sourceStoragePath(): ?string
    {
        $source = $this->source;

        if ($source instanceof InboxAttachment) {
            return $source->storage_path;
        }

        if ($source instanceof UploadedPdfSource) {
            return $source->source_pdf_path;
        }

        return null;
    }

    public function sourcePreviewContents(): ?string
    {
        $disk = $this->sourceStorageDisk();
        $path = $this->sourceStoragePath();

        if (! is_string($disk) || $disk === '' || ! is_string($path) || $path === '') {
            return null;
        }

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        return is_string($contents) ? $contents : null;
    }

    /**
     * @return MorphToMany<KositValidation, $this>
     */
    public function kositValidations(): MorphToMany
    {
        return $this->morphPivotRelation('kosit_validatables');
    }

    public function latestKositValidation(): ?KositValidation
    {
        if ($this->relationLoaded('kositValidations')) {
            /** @var KositValidation|null $latest */
            $latest = $this->kositValidations
                ->sortByDesc(function (KositValidation $validation): string {
                    $validatedAt = $validation->validated_at?->format('Y-m-d H:i:s.u') ?? '';

                    return $validatedAt.':'.$validation->getKey();
                })
                ->first();

            return $latest;
        }

        return $this->kositValidations()->orderByDesc('validated_at')->orderByDesc('id')->first();
    }

    /**
     * @return MorphToMany<VeraPdfValidation, $this>
     */
    public function veraPdfValidations(): MorphToMany
    {
        return $this->morphPivotRelation('verapdf_validatables');
    }

    public function latestVeraPdfValidation(): ?VeraPdfValidation
    {
        if ($this->relationLoaded('veraPdfValidations')) {
            /** @var VeraPdfValidation|null $latest */
            $latest = $this->veraPdfValidations
                ->sortByDesc(function (VeraPdfValidation $validation): string {
                    $validatedAt = $validation->validated_at?->format('Y-m-d H:i:s.u') ?? '';

                    return $validatedAt.':'.$validation->getKey();
                })
                ->first();

            return $latest;
        }

        return $this->veraPdfValidations()->orderByDesc('validated_at')->orderByDesc('id')->first();
    }

    public function isDeliverable(): bool
    {
        return $this->gateway_status === EBillingAttachmentProcessingStatus::Validated
            && is_string($this->artifact_content_hash)
            && $this->artifact_content_hash !== '';
    }

    public function deliverableStoragePath(ArtifactKind $artifactKind): ?string
    {
        return match ($artifactKind) {
            ArtifactKind::Xml => $this->xml_storage_path,
            ArtifactKind::Pdf => $this->pdf_storage_path,
        };
    }

    /**
     * Visible PDF for humans: the hybrid invoice PDF, or the watermarked XRechnung copy.
     * Never treats the copy as the hybrid deliverable of record.
     */
    public function humanReadablePdfStoragePath(): ?string
    {
        if (is_string($this->pdf_storage_path) && $this->pdf_storage_path !== '') {
            return $this->pdf_storage_path;
        }

        if (is_string($this->copy_pdf_storage_path) && $this->copy_pdf_storage_path !== '') {
            return $this->copy_pdf_storage_path;
        }

        return null;
    }

    /**
     * Customer-facing download name derived from the source original filename.
     * Keeps storage paths opaque while downloads stay human-readable.
     */
    public function downloadFilenameForStoredPath(string $storagePath): string
    {
        try {
            $base = EBillingArtifactNaming::basenameFor($this->sourceOriginalFilename());
        } catch (RuntimeException) {
            return basename($storagePath);
        }

        if ($storagePath === $this->copy_pdf_storage_path) {
            return $base.'_copy.pdf';
        }

        if ($storagePath === $this->xml_storage_path) {
            return $base.'.xml';
        }

        if ($storagePath === $this->pdf_storage_path) {
            return $base.'.pdf';
        }

        return basename($storagePath);
    }

    /**
     * @param  Builder<EbillingDocument>  $query
     * @return Builder<EbillingDocument>
     */
    public function scopeWhereLatestKositValidationPassed(Builder $query, bool $passed): Builder
    {
        $morphClass = $query->getModel()->getMorphClass();
        $documentsTable = $query->getModel()->getTable();

        return $query->whereExists(function ($exists) use ($morphClass, $documentsTable, $passed): void {
            $exists->selectRaw('1')
                ->from('kosit_validations as latest_kv')
                ->join('kosit_validatables as latest_kvt', 'latest_kvt.kosit_validation_id', '=', 'latest_kv.id')
                ->whereColumn('latest_kvt.validatable_id', "{$documentsTable}.id")
                ->where('latest_kvt.validatable_type', $morphClass)
                ->where('latest_kv.passed', $passed)
                ->whereRaw(
                    "latest_kv.id = (
                        SELECT kv2.id
                        FROM kosit_validatables kvt2
                        INNER JOIN kosit_validations kv2 ON kv2.id = kvt2.kosit_validation_id
                        WHERE kvt2.validatable_type = ?
                        AND kvt2.validatable_id = {$documentsTable}.id
                        ORDER BY kv2.validated_at DESC, kv2.id DESC
                        LIMIT 1
                    )",
                    [$morphClass],
                );
        });
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModels::invoice(), 'invoice_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Line-level field validations are stored under {@see $field_validations}['lines'] keyed by line id.
     *
     * @return Collection<int, never>
     */
    public function fieldValidationItems(): Collection
    {
        return collect();
    }

    /**
     * @param  Builder<EbillingDocument>  $query
     * @return Builder<EbillingDocument>
     */
    public function scopeNeedsHumanReview(Builder $query): Builder
    {
        return $query
            ->whereIn('review_status', [
                InvoiceProcessingStatus::ParserCreated->value,
                InvoiceProcessingStatus::DbValidated->value,
            ])
            ->where(function (Builder $outer): void {
                self::applyScopeConfiguredFieldBlocksReview($outer);
            });
    }

    public static function fieldValidationsNeedHumanReview(?array $fieldValidations, ?array $severityReleases): bool
    {
        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);

        if (! is_array($invoiceFields)) {
            $invoiceFields = [];
        }
        if (! is_array($lineFields)) {
            $lineFields = [];
        }

        $validations = is_array($fieldValidations) ? $fieldValidations : [];

        foreach ($invoiceFields as $field => $priority) {
            if (! is_string($field) || ! is_string($priority)) {
                continue;
            }

            $status = self::readFieldStatusFromValidations($validations, $field);

            if (self::configuredFieldBlocksReview($status, $priority, $severityReleases, $field)) {
                return true;
            }
        }

        foreach (self::readLineFieldValidationsFromArray($validations) as $lineId => $lineFieldValidations) {
            foreach ($lineFields as $field => $priority) {
                if (! is_string($field) || ! is_string($priority)) {
                    continue;
                }

                $status = self::readFieldStatusFromValidations($lineFieldValidations, $field);

                if (self::configuredFieldBlocksReview($status, $priority, $severityReleases, $field, $lineId)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function configuredFieldBlocksReview(
        ?string $status,
        string $priority,
        ?array $severityReleases,
        string $field,
        ?string $lineId = null,
    ): bool {
        if (! in_array($priority, ['must', 'should'], true)) {
            return false;
        }

        if ($status === 'needs_review') {
            return true;
        }

        if ($status !== 'missing') {
            return false;
        }

        if ($priority === 'must') {
            return true;
        }

        return ! self::hasValidSeverityRelease($severityReleases, $field, $lineId);
    }

    public static function hasValidSeverityRelease(?array $severityReleases, string $field, ?string $lineId = null): bool
    {
        return self::severityReleaseEntryIsValid(
            self::readSeverityReleaseEntry($severityReleases, $field, $lineId),
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function readSeverityReleaseEntry(?array $severityReleases, string $field, ?string $lineId = null): ?array
    {
        if (! is_array($severityReleases)) {
            return null;
        }

        if ($lineId !== null) {
            $lines = $severityReleases['lines'] ?? null;
            if (! is_array($lines)) {
                return null;
            }

            $lineReleases = $lines[$lineId] ?? null;
            if (! is_array($lineReleases)) {
                return null;
            }

            $entry = $lineReleases[$field] ?? null;
        } else {
            $entry = $severityReleases[$field] ?? null;
        }

        return is_array($entry) ? $entry : null;
    }

    /**
     * @param  array<string, mixed>|null  $entry
     */
    public static function severityReleaseEntryIsValid(?array $entry): bool
    {
        if ($entry === null) {
            return false;
        }

        $reason = $entry['reason'] ?? null;
        $releasedAt = $entry['released_at'] ?? null;

        if (! is_string($reason) || trim($reason) === '') {
            return false;
        }

        if (! is_string($releasedAt) || trim($releasedAt) === '') {
            return false;
        }

        $releasedById = $entry['released_by_id'] ?? null;

        if ($releasedById === null || $releasedById === '') {
            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $validations
     */
    public static function readFieldStatusFromValidations(?array $validations, string $field): ?string
    {
        if (! is_array($validations) || ! isset($validations[$field]) || ! is_array($validations[$field])) {
            return null;
        }

        $status = $validations[$field]['status'] ?? null;

        return is_string($status) ? $status : null;
    }

    /**
     * @param  array<string, mixed>|null  $fieldValidations
     * @return array<string, array<string, mixed>>
     */
    public static function readLineFieldValidationsFromArray(?array $fieldValidations): array
    {
        if (! is_array($fieldValidations)) {
            return [];
        }

        $lines = $fieldValidations['lines'] ?? null;
        if (! is_array($lines)) {
            return [];
        }

        $normalized = [];

        foreach ($lines as $lineId => $lineFieldValidations) {
            if (! is_array($lineFieldValidations)) {
                continue;
            }

            $normalized[(string) $lineId] = $lineFieldValidations;
        }

        return $normalized;
    }

    public static function fieldValidationAllowsValidatedTransition(
        ?string $status,
        string $priority,
        ?array $severityReleases,
        string $field,
        ?string $lineId = null,
    ): bool {
        if (! in_array($priority, ['must', 'should'], true)) {
            return true;
        }

        if (in_array($status, ['validated', 'db_validated', 'not_applicable', 'parsed'], true)) {
            return true;
        }

        if ($status === 'missing' && $priority === 'should' && self::hasValidSeverityRelease($severityReleases, $field, $lineId)) {
            return true;
        }

        return false;
    }

    public function getValidationScoreAttribute(): ?int
    {
        $raw = $this->getAttributes()['validation_score'] ?? null;

        if ($raw !== null && $raw !== '') {
            return (int) $raw;
        }

        return $this->calculateValidationScore();
    }

    /**
     * Computes the validation score from `field_validations` JSON on the document.
     * Used to materialize {@see $validation_score} and as a fallback when the column is null.
     */
    public function calculateValidationScore(): ?int
    {
        $invoiceFv = is_array($this->field_validations) ? $this->field_validations : [];
        $linesFv = $invoiceFv['lines'] ?? null;
        $hasLineFv = is_array($linesFv) && $linesFv !== [];

        if ($invoiceFv === [] && ! $hasLineFv) {
            return null;
        }

        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);

        if (! is_array($invoiceFields)) {
            $invoiceFields = [];
        }
        if (! is_array($lineFields)) {
            $lineFields = [];
        }

        $total = 0;
        $valid = 0;

        foreach ($invoiceFields as $field => $priority) {
            if (! is_string($field) || ! is_string($priority)) {
                continue;
            }
            if (! in_array($priority, ['must', 'should'], true)) {
                continue;
            }
            $total++;
            $status = $this->readFieldStatus($this->field_validations, $field);
            if ($this->statusCountsTowardValidationScore($status)) {
                $valid++;
            }
        }

        if (is_array($linesFv)) {
            foreach ($linesFv as $lineFieldValidations) {
                if (! is_array($lineFieldValidations)) {
                    continue;
                }
                foreach ($lineFields as $field => $priority) {
                    if (! is_string($field) || ! is_string($priority)) {
                        continue;
                    }
                    if (! in_array($priority, ['must', 'should'], true)) {
                        continue;
                    }
                    $total++;
                    $status = $this->readFieldStatus($lineFieldValidations, $field);
                    if ($this->statusCountsTowardValidationScore($status)) {
                        $valid++;
                    }
                }
            }
        }

        if ($total === 0) {
            return null;
        }

        return (int) round(($valid / $total) * 100);
    }

    public function transitionTo(InvoiceProcessingStatus $newStatus): void
    {
        $current = $this->resolveReviewStatusEnum();

        if ($current === $newStatus) {
            return;
        }

        if (! $current->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot transition invoice processing status from %s to %s.',
                    $current->value,
                    $newStatus->value
                )
            );
        }

        $this->review_status = $newStatus;
        $this->save();
    }

    public function isFullyValidated(): bool
    {
        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        if (! is_array($invoiceFields)) {
            return true;
        }

        foreach ($invoiceFields as $field => $priority) {
            if ($priority !== 'must') {
                continue;
            }
            $status = $this->readFieldStatus($this->field_validations, (string) $field);
            if (! $this->statusIsFullyValidated($status)) {
                return false;
            }
        }

        return true;
    }

    public function needsHumanReview(): bool
    {
        return self::fieldValidationsNeedHumanReview(
            is_array($this->field_validations) ? $this->field_validations : null,
            is_array($this->severity_releases) ? $this->severity_releases : null,
        );
    }

    public function hasSeverityRelease(string $field, ?string $lineId = null): bool
    {
        return self::hasValidSeverityRelease(
            is_array($this->severity_releases) ? $this->severity_releases : null,
            $field,
            $lineId,
        );
    }

    public function resolveConfiguredFieldPriority(string $field, bool $isLineField = false): string
    {
        $configKey = $isLineField ? 'invoice_line_fields' : 'invoice_fields';
        $fields = config("e-billing.field_validation.{$configKey}", []);

        if (! is_array($fields)) {
            return 'could';
        }

        $priority = $fields[$field] ?? null;

        return is_string($priority) ? $priority : 'could';
    }

    public function resolveFieldValidationStatus(string $field, ?string $lineId = null): ?string
    {
        $validations = is_array($this->field_validations) ? $this->field_validations : [];

        if ($lineId !== null) {
            $lineFieldValidations = self::readLineFieldValidationsFromArray($validations)[$lineId] ?? null;

            return is_array($lineFieldValidations)
                ? self::readFieldStatusFromValidations($lineFieldValidations, $field)
                : null;
        }

        return self::readFieldStatusFromValidations($validations, $field);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function readLineFieldValidations(): array
    {
        return self::readLineFieldValidationsFromArray(
            is_array($this->field_validations) ? $this->field_validations : null,
        );
    }

    /**
     * @return array{status: string, source?: string, matched_id?: string}|null
     */
    public function getFieldValidation(string $fieldName): ?array
    {
        $all = $this->field_validations;

        if (! is_array($all) || ! isset($all[$fieldName]) || ! is_array($all[$fieldName])) {
            return null;
        }

        /** @var array{status: string, source?: string, matched_id?: string} $entry */
        $entry = $all[$fieldName];

        return $entry;
    }

    public function setFieldValidation(
        string $fieldName,
        string $status,
        ?string $source = null,
        ?string $matchedId = null,
    ): void {
        $all = is_array($this->field_validations) ? $this->field_validations : [];
        $entry = ['status' => $status];
        if ($source !== null) {
            $entry['source'] = $source;
        }
        if ($matchedId !== null) {
            $entry['matched_id'] = $matchedId;
        }
        $all[$fieldName] = $entry;
        $this->field_validations = $all;
        $this->save();
    }

    /**
     * @param  array<int, mixed>  $parameters
     */
    public function __call($method, $parameters): mixed
    {
        return $this->morphPivotCall($method, $parameters);
    }

    private function resolveReviewStatusEnum(): InvoiceProcessingStatus
    {
        $value = $this->review_status;
        if ($value instanceof InvoiceProcessingStatus) {
            return $value;
        }

        $raw = $this->getAttributes()['review_status'] ?? InvoiceProcessingStatus::ParserCreated->value;

        return InvoiceProcessingStatus::from((string) $raw);
    }

    /**
     * @param  array<string, mixed>|null  $validations
     */
    private function readFieldStatus(?array $validations, string $field): ?string
    {
        return self::readFieldStatusFromValidations($validations, $field);
    }

    /**
     * @param  Builder<EbillingDocument>  $query
     */
    private static function applyScopeConfiguredFieldBlocksReview(Builder $query): void
    {
        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);

        if (! is_array($invoiceFields)) {
            $invoiceFields = [];
        }
        if (! is_array($lineFields)) {
            $lineFields = [];
        }

        $fvColumn = $query->qualifyColumn('field_validations');
        $srColumn = $query->qualifyColumn('severity_releases');

        $connection = $query->getConnection();
        $driver = match (true) {
            $connection instanceof MySqlConnection => 'mysql',
            $connection instanceof SQLiteConnection => 'sqlite',
            default => 'sqlite',
        };

        $query->where(function (Builder $orQuery) use ($invoiceFields, $lineFields, $fvColumn, $srColumn, $driver): void {
            $hasCondition = false;

            foreach ($invoiceFields as $field => $priority) {
                if (! is_string($field) || ! is_string($priority)) {
                    continue;
                }
                if (! in_array($priority, ['must', 'should'], true)) {
                    continue;
                }

                $hasCondition = true;
                $orQuery->orWhere(function (Builder $fieldQuery) use ($field, $priority, $fvColumn, $srColumn, $driver): void {
                    self::applyScopeInvoiceFieldBlocksReview($fieldQuery, $field, $priority, $fvColumn, $srColumn, $driver);
                });
            }

            foreach ($lineFields as $field => $priority) {
                if (! is_string($field) || ! is_string($priority)) {
                    continue;
                }
                if (! in_array($priority, ['must', 'should'], true)) {
                    continue;
                }

                $hasCondition = true;
                $orQuery->orWhere(function (Builder $fieldQuery) use ($field, $priority, $fvColumn, $srColumn, $driver): void {
                    self::applyScopeLineFieldBlocksReview($fieldQuery, $field, $priority, $fvColumn, $srColumn, $driver);
                });
            }

            if (! $hasCondition) {
                $orQuery->whereRaw('0 = 1');
            }
        });
    }

    /**
     * @param  Builder<EbillingDocument>  $query
     */
    private static function applyScopeInvoiceFieldBlocksReview(
        Builder $query,
        string $field,
        string $priority,
        string $fvColumn,
        string $srColumn,
        string $driver,
    ): void {
        $statusPath = '$.'.$field.'.status';
        $statusExpr = self::sqlJsonExtract($fvColumn, $statusPath, $driver);

        $query->where(function (Builder $statusQuery) use ($statusExpr, $priority, $srColumn, $field, $driver): void {
            $statusQuery->whereRaw("{$statusExpr} = ?", ['needs_review']);

            if ($priority === 'must') {
                $statusQuery->orWhereRaw("{$statusExpr} = ?", ['missing']);

                return;
            }

            $releaseValid = self::sqlInvoiceSeverityReleaseIsValid($srColumn, $field, $driver);
            $statusQuery->orWhereRaw("({$statusExpr} = ? AND NOT ({$releaseValid}))", ['missing']);
        });
    }

    /**
     * @param  Builder<EbillingDocument>  $query
     */
    private static function applyScopeLineFieldBlocksReview(
        Builder $query,
        string $field,
        string $priority,
        string $fvColumn,
        string $srColumn,
        string $driver,
    ): void {
        if ($driver === 'mysql') {
            $releaseValid = self::sqlMySqlLineSeverityReleaseIsValid($srColumn, 'lk.line_key', $field);
            $statusPath = "CONCAT('$.lines.', lk.line_key, '.{$field}.status')";
            $statusExpr = "JSON_UNQUOTE(JSON_EXTRACT({$fvColumn}, {$statusPath}))";

            if ($priority === 'must') {
                $query->whereRaw(
                    "EXISTS (
                        SELECT 1
                        FROM JSON_TABLE(
                            IFNULL(JSON_KEYS(IFNULL(JSON_EXTRACT({$fvColumn}, '$.lines'), JSON_OBJECT())), JSON_ARRAY()),
                            '\$[*]' COLUMNS (line_key VARCHAR(191) PATH '\$')
                        ) AS lk
                        WHERE {$statusExpr} IN ('missing', 'needs_review')
                    )",
                );

                return;
            }

            $query->whereRaw(
                "EXISTS (
                    SELECT 1
                    FROM JSON_TABLE(
                        IFNULL(JSON_KEYS(IFNULL(JSON_EXTRACT({$fvColumn}, '$.lines'), JSON_OBJECT())), JSON_ARRAY()),
                        '\$[*]' COLUMNS (line_key VARCHAR(191) PATH '\$')
                    ) AS lk
                    WHERE {$statusExpr} = 'needs_review'
                       OR ({$statusExpr} = 'missing' AND NOT ({$releaseValid}))
                )",
            );

            return;
        }

        $statusExpr = "json_extract(line_row.value, '$.{$field}.status')";
        $releaseValid = self::sqlSqliteLineSeverityReleaseIsValid($srColumn, 'line_row.key', $field);

        if ($priority === 'must') {
            $query->whereRaw(
                "EXISTS (
                    SELECT 1
                    FROM json_each(json_extract({$fvColumn}, '$.lines')) AS line_row
                    WHERE {$statusExpr} IN ('missing', 'needs_review')
                )",
            );

            return;
        }

        $query->whereRaw(
            "EXISTS (
                SELECT 1
                FROM json_each(json_extract({$fvColumn}, '$.lines')) AS line_row
                WHERE {$statusExpr} = 'needs_review'
                   OR ({$statusExpr} = 'missing' AND NOT ({$releaseValid}))
            )",
        );
    }

    private static function sqlJsonExtract(string $column, string $path, string $driver): string
    {
        return match ($driver) {
            'mysql' => "JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$path}'))",
            default => "json_extract({$column}, '{$path}')",
        };
    }

    private static function sqlInvoiceSeverityReleaseIsValid(string $srColumn, string $field, string $driver): string
    {
        $reasonPath = '$.'.$field.'.reason';
        $releasedAtPath = '$.'.$field.'.released_at';

        $releasedByIdPath = '$.'.$field.'.released_by_id';

        if ($driver === 'mysql') {
            $reason = "NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT({$srColumn}, '{$reasonPath}'))), '')";
            $releasedAt = "NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT({$srColumn}, '{$releasedAtPath}'))), '')";
            $releasedById = "JSON_EXTRACT({$srColumn}, '{$releasedByIdPath}')";

            return "({$reason} IS NOT NULL AND {$releasedAt} IS NOT NULL AND {$releasedById} IS NOT NULL)";
        }

        $reason = "NULLIF(trim(json_extract({$srColumn}, '{$reasonPath}')), '')";
        $releasedAt = "NULLIF(trim(json_extract({$srColumn}, '{$releasedAtPath}')), '')";
        $releasedById = "json_extract({$srColumn}, '{$releasedByIdPath}')";

        return "({$reason} IS NOT NULL AND {$releasedAt} IS NOT NULL AND {$releasedById} IS NOT NULL)";
    }

    private static function sqlSqliteLineSeverityReleaseIsValid(string $srColumn, string $lineKeyExpr, string $field): string
    {
        $reason = "NULLIF(trim(json_extract({$srColumn}, '$.lines.' || {$lineKeyExpr} || '.{$field}.reason')), '')";
        $releasedAt = "NULLIF(trim(json_extract({$srColumn}, '$.lines.' || {$lineKeyExpr} || '.{$field}.released_at')), '')";
        $releasedById = "json_extract({$srColumn}, '$.lines.' || {$lineKeyExpr} || '.{$field}.released_by_id')";

        return "({$reason} IS NOT NULL AND {$releasedAt} IS NOT NULL AND {$releasedById} IS NOT NULL)";
    }

    private static function sqlMySqlLineSeverityReleaseIsValid(string $srColumn, string $lineKeyExpr, string $field): string
    {
        $reasonPath = "CONCAT('$.lines.', {$lineKeyExpr}, '.{$field}.reason')";
        $releasedAtPath = "CONCAT('$.lines.', {$lineKeyExpr}, '.{$field}.released_at')";
        $releasedByIdPath = "CONCAT('$.lines.', {$lineKeyExpr}, '.{$field}.released_by_id')";
        $reason = "NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT({$srColumn}, {$reasonPath}))), '')";
        $releasedAt = "NULLIF(TRIM(JSON_UNQUOTE(JSON_EXTRACT({$srColumn}, {$releasedAtPath}))), '')";
        $releasedById = "JSON_EXTRACT({$srColumn}, {$releasedByIdPath})";

        return "({$reason} IS NOT NULL AND {$releasedAt} IS NOT NULL AND {$releasedById} IS NOT NULL)";
    }

    private function statusIsFullyValidated(?string $status): bool
    {
        return in_array($status, ['validated', 'db_validated'], true);
    }

    private function statusCountsTowardValidationScore(?string $status): bool
    {
        return in_array($status, ['validated', 'db_validated', 'not_applicable', 'parsed'], true);
    }
}
