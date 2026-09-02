<?php

declare(strict_types=1);

namespace Moox\Invoice\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Invoice\Database\Factories\InvoiceFactory;
use Moox\Invoice\Support\En16931\Casts\DeliveryPartyCast;
use Moox\Invoice\Support\En16931\Casts\PartyCast;
use Moox\Invoice\Support\En16931\Casts\PaymentMeansCast;
use Moox\Invoice\Support\En16931\Party;
use Moox\Invoice\Support\En16931\PaymentMeans;
use Moox\Invoice\Support\InvoiceModels;

/**
 * @property Party|null $seller
 * @property Party|null $buyer
 * @property Party|null $delivery
 * @property PaymentMeans|null $payment_means
 * @property int $document_version
 * @property bool $is_current
 * @property list<string>|null $notes
 */
class Invoice extends BaseItemModel
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'document_type',
        'document_version',
        'is_current',
        'due_date',
        'currency',
        'customer_number',
        'customer_reference',
        'order_number',
        'order_date',
        'delivery_date',
        'payment_terms',
        'shipping_method',
        'delivery_terms',
        'notes',
        'seller',
        'buyer',
        'delivery',
        'payment_means',
        'net_total',
        'vat_rate',
        'vat_amount',
        'gross_total',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'seller' => PartyCast::class,
            'buyer' => PartyCast::class,
            'delivery' => DeliveryPartyCast::class,
            'payment_means' => PaymentMeansCast::class,
            'document_version' => 'integer',
            'is_current' => 'boolean',
            'notes' => 'array',
            'net_total' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            $invoice->assignDocumentVersionDefaults();
        });
    }

    public static function getResourceName(): string
    {
        return 'invoice';
    }

    public static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * All non-deleted rows that share this invoice number and document type (including self).
     *
     * @return Collection<int, static>
     */
    public function versionFamily(): Collection
    {
        $number = $this->invoice_number;
        $type = $this->document_type;

        if (! is_string($number) || trim($number) === '' || $type === null || $type === '') {
            return collect([$this]);
        }

        return static::query()
            ->where('invoice_number', $number)
            ->where('document_type', $type)
            ->orderBy('document_version')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Assign next document_version and is_current for the number/type family.
     * First of a number is current; later uploads start as non-current until confirmed.
     */
    public function assignDocumentVersionDefaults(): void
    {
        $number = $this->invoice_number;
        $type = $this->document_type;

        if (! is_string($number) || trim($number) === '' || $type === null || $type === '') {
            if ($this->document_version === null) {
                $this->document_version = 1;
            }
            if ($this->is_current === null) {
                $this->is_current = true;
            }

            return;
        }

        $siblings = static::query()
            ->where('invoice_number', $number)
            ->where('document_type', $type);

        if ($this->document_version === null) {
            $maxVersion = (int) (clone $siblings)->max('document_version');
            $this->document_version = $maxVersion + 1;
        }

        if ($this->is_current === null) {
            $this->is_current = ! (clone $siblings)->where('is_current', true)->exists();
        }
    }

    /**
     * Mark this invoice as the current version of its number/type family.
     * Sibling versions remain stored with is_current = false.
     */
    public function makeCurrentVersion(): void
    {
        $number = $this->invoice_number;
        $type = $this->document_type;

        if (! is_string($number) || trim($number) === '' || $type === null || $type === '') {
            $this->forceFill(['is_current' => true])->save();

            return;
        }

        static::query()
            ->where('invoice_number', $number)
            ->where('document_type', $type)
            ->whereKeyNot($this->getKey())
            ->update(['is_current' => false]);

        $this->forceFill(['is_current' => true])->save();
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceModels::invoiceLine());
    }

    /**
     * @return MorphMany<InvoiceAllowanceCharge, $this>
     */
    public function allowanceCharges(): MorphMany
    {
        return $this->morphMany(InvoiceModels::invoiceAllowanceCharge(), 'chargeable');
    }
}
