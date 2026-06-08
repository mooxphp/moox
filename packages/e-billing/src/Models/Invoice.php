<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Moox\EBilling\Enums\InvoiceProcessingStatus;
use Moox\MailInbox\Models\InboxAttachment;

/**
 * @property InvoiceProcessingStatus|null $processing_status
 * @property array<string, mixed>|null $field_validations
 * @property int|null $validation_score
 * @property string|null $country Billing-country projection derived only from the buyer/customer Address DTO.
 */
class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inbox_attachment_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'document_type',
        'due_date',
        'currency',
        'customer_number',
        'customer_name',
        'customer_address',
        'country',
        'customer_vat_id',
        'customer_reference',
        'order_number',
        'order_date',
        'delivery_address',
        'supplier_name',
        'supplier_vat_id',
        'supplier_tax_number',
        'supplier_address',
        'supplier_number',
        'supplier_phone',
        'supplier_email',
        'supplier_bank_accounts',
        'agent',
        'payment_terms',
        'pricing_basis',
        'shipping_method',
        'net_total',
        'vat_rate',
        'vat_amount',
        'gross_total',
        'discount_percent',
        'discount_amount',
        'shipping_cost',
        'minimum_quantity_surcharge',
        'freight_flat_rate',
        'packaging_cost',
        'notes',
        'processing_status',
        'field_validations',
        'validation_score',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'customer_address' => 'array',
            // Billing-country projection: write from InvoiceFactory using customerAddress.country only.
            'country' => 'string',
            'delivery_address' => 'array',
            'supplier_address' => 'array',
            'supplier_bank_accounts' => 'array',
            'notes' => 'array',
            'field_validations' => 'array',
            'validation_score' => 'integer',
            'net_total' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'minimum_quantity_surcharge' => 'decimal:2',
            'freight_flat_rate' => 'decimal:2',
            'packaging_cost' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'processing_status' => InvoiceProcessingStatus::class,
        ];
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<InboxAttachment, $this>
     */
    public function inboxAttachment(): BelongsTo
    {
        return $this->belongsTo(InboxAttachment::class, 'inbox_attachment_id');
    }

    /**
     * Returns field_validations from all invoice lines.
     *
     * Uses eager-loaded lines if available (e.g., detail view) for performance.
     * Falls back to a narrow query selecting only field_validations (e.g., list view).
     *
     * Note: The eager-loaded path may return stale data if lines were modified
     * after loading. In the list view this is not an issue since lines are not
     * eager-loaded. In the detail view, the data is loaded fresh per request.
     *
     * @return Collection<int, InvoiceLine>
     */
    public function fieldValidationItems(): Collection
    {
        if ($this->relationLoaded('lines')) {
            return $this->lines;
        }

        if (! $this->exists) {
            return collect();
        }

        return InvoiceLine::query()
            ->where('invoice_id', $this->id)
            ->orderBy('position')
            ->select(['id', 'invoice_id', 'position', 'field_validations'])
            ->get();
    }

    /**
     * @param  Builder<Invoice>  $query
     * @return Builder<Invoice>
     */
    public function scopeNeedsHumanReview(Builder $query): Builder
    {
        return $query
            ->whereIn('processing_status', [
                InvoiceProcessingStatus::ParserCreated->value,
                InvoiceProcessingStatus::DbValidated->value,
            ])
            ->where(function (Builder $outer): void {
                $outer
                    ->where(function (Builder $invoiceQuery): void {
                        self::applyJsonHasProblematicFieldStatus($invoiceQuery, 'field_validations');
                    })
                    ->orWhereHas('lines', function (Builder $lines): void {
                        self::applyJsonHasProblematicFieldStatus($lines, 'field_validations');
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
     * Computes the validation score from `field_validations` JSON on the invoice and its lines.
     * Used to materialize {@see $validation_score} and as a fallback when the column is null.
     */
    public function calculateValidationScore(): ?int
    {
        $invoiceFv = is_array($this->field_validations) ? $this->field_validations : [];
        $lines = $this->fieldValidationItems();
        $anyLineFv = $lines->contains(fn (InvoiceLine $line): bool => is_array($line->field_validations) && $line->field_validations !== []);

        if ($invoiceFv === [] && ! $anyLineFv) {
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

        foreach ($lines as $line) {
            foreach ($lineFields as $field => $priority) {
                if (! is_string($field) || ! is_string($priority)) {
                    continue;
                }
                if (! in_array($priority, ['must', 'should'], true)) {
                    continue;
                }
                $total++;
                $status = $this->readFieldStatus($line->field_validations, $field);
                if ($this->statusCountsTowardValidationScore($status)) {
                    $valid++;
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
        $current = $this->resolveProcessingStatusEnum();

        if (! $current->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Cannot transition invoice processing status from %s to %s.',
                    $current->value,
                    $newStatus->value
                )
            );
        }

        $this->processing_status = $newStatus;
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

        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);
        if (! is_array($lineFields)) {
            return true;
        }

        foreach ($this->lines as $line) {
            foreach ($lineFields as $field => $priority) {
                if ($priority !== 'must') {
                    continue;
                }
                $status = $this->readFieldStatus($line->field_validations, (string) $field);
                if (! $this->statusIsFullyValidated($status)) {
                    return false;
                }
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

        $lineFields = config('e-billing.field_validation.invoice_line_fields', []);
        if (! is_array($lineFields)) {
            return false;
        }

        foreach ($this->lines as $line) {
            foreach ($lineFields as $field => $priority) {
                if (! in_array($priority, ['must', 'should'], true)) {
                    continue;
                }
                $status = $this->readFieldStatus($line->field_validations, (string) $field);
                if (in_array($status, ['needs_review', 'missing'], true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array{status: string, source?: string, matched_id?: int}|null
     */
    public function getFieldValidation(string $fieldName): ?array
    {
        $all = $this->field_validations;

        if (! is_array($all) || ! isset($all[$fieldName]) || ! is_array($all[$fieldName])) {
            return null;
        }

        /** @var array{status: string, source?: string, matched_id?: int} $entry */
        $entry = $all[$fieldName];

        return $entry;
    }

    public function setFieldValidation(string $fieldName, string $status, ?string $source = null, ?int $matchedId = null): void
    {
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

    private function resolveProcessingStatusEnum(): InvoiceProcessingStatus
    {
        $value = $this->processing_status;
        if ($value instanceof InvoiceProcessingStatus) {
            return $value;
        }

        $raw = $this->getAttributes()['processing_status'] ?? InvoiceProcessingStatus::ParserCreated->value;

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
            $query->whereRaw(
                "({$qualified} IS NOT NULL AND (JSON_SEARCH({$qualified}, 'one', 'needs_review', NULL, '\$**.status') IS NOT NULL OR JSON_SEARCH({$qualified}, 'one', 'missing', NULL, '\$**.status') IS NOT NULL))"
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
