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
use InvalidArgumentException;
use Moox\Company\Models\Company;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Core\Traits\MorphPivot\HasMorphPivotRelations;
use Moox\EBilling\Enums\EBillingAttachmentProcessingStatus;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\EBilling\Formats\ArtifactKind;
use Moox\Invoice\Models\Invoice;
use Moox\KositValidator\Models\KositValidation;
use Moox\MailInbox\Models\InboxAttachment;
use Moox\VeraPdf\Models\VeraPdfValidation;

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
 * @property string $format
 * @property string|null $artifact_content_hash
 * @property array<string, mixed>|null $ignored_reason
 * @property EBillingAttachmentProcessingStatus|null $gateway_status
 * @property InvoiceProcessingStatus|null $review_status
 * @property array<string, mixed>|null $field_validations
 * @property string|null $invoice_id
 * @property string|null $company_id
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
     * @var list<string>
     */
    protected $fillable = [
        'source_type',
        'source_id',
        'bill_data',
        'xml_storage_path',
        'storage_disk',
        'pdf_storage_path',
        'format',
        'artifact_content_hash',
        'ignored_reason',
        'gateway_status',
        'review_status',
        'validation_score',
        'field_validations',
        'processed_at',
        'error_message',
        'invoice_id',
        'company_id',
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
            'field_validations' => 'array',
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
        return $this->belongsTo(Invoice::class, 'invoice_id'); // Extend Invoice in your host app if needed
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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
                $outer->where(function (Builder $documentQuery): void {
                    self::applyJsonHasProblematicFieldStatus($documentQuery, 'field_validations');
                });
            });
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
        $invoiceFields = config('e-billing.field_validation.invoice_fields', []);
        if (is_array($invoiceFields)) {
            foreach ($invoiceFields as $field => $priority) {
                if (! in_array($priority, ['must', 'should'], true)) {
                    continue;
                }
                $status = $this->readFieldStatus($this->field_validations, (string) $field);
                if (in_array($status, ['needs_review', 'missing'], true)) {
                    return true;
                }
            }
        }

        $linesFv = is_array($this->field_validations) ? ($this->field_validations['lines'] ?? null) : null;
        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);

        if (is_array($linesFv) && is_array($lineFields)) {
            foreach ($linesFv as $lineFieldValidations) {
                if (! is_array($lineFieldValidations)) {
                    continue;
                }
                foreach ($lineFields as $field => $priority) {
                    if (! in_array($priority, ['must', 'should'], true)) {
                        continue;
                    }
                    $status = $this->readFieldStatus($lineFieldValidations, (string) $field);
                    if (in_array($status, ['needs_review', 'missing'], true)) {
                        return true;
                    }
                }
            }
        }

        return false;
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
        if (! is_array($validations) || ! isset($validations[$field]) || ! is_array($validations[$field])) {
            return null;
        }

        $status = $validations[$field]['status'] ?? null;

        return is_string($status) ? $status : null;
    }

    private function statusIsFullyValidated(?string $status): bool
    {
        return in_array($status, ['validated', 'db_validated'], true);
    }

    private static function applyJsonHasProblematicFieldStatus(Builder $query, string $column): void
    {
        $qualified = $query->qualifyColumn($column);
        $connection = $query->getConnection();
        $driver = match (true) {
            $connection instanceof MySqlConnection => 'mysql',
            $connection instanceof SQLiteConnection => 'sqlite',
            default => 'sqlite',
        };

        if ($driver === 'mysql') {
            $needsReviewSearch = "JSON_SEARCH({$qualified}, 'one', 'needs_review', NULL, '\$**.status') IS NOT NULL";
            $missingSearch = "JSON_SEARCH({$qualified}, 'one', 'missing', NULL, '\$**.status') IS NOT NULL";
            $query->whereRaw(
                "({$qualified} IS NOT NULL AND ({$needsReviewSearch} OR {$missingSearch}))"
            );

            return;
        }

        $query->whereRaw(
            "({$qualified} IS NOT NULL AND ({$qualified} LIKE ? OR {$qualified} LIKE ?))",
            ['%"status":"needs_review"%', '%"status":"missing"%']
        );
    }

    private function statusCountsTowardValidationScore(?string $status): bool
    {
        return in_array($status, ['validated', 'db_validated', 'not_applicable', 'parsed'], true);
    }
}
