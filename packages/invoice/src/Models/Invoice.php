<?php

declare(strict_types=1);

namespace Moox\Invoice\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Invoice\Database\Factories\InvoiceFactory;
use Moox\Invoice\Support\En16931\Address;
use Moox\Invoice\Support\En16931\Casts\AddressCast;
use Moox\Invoice\Support\En16931\Casts\PartyCast;
use Moox\Invoice\Support\En16931\Casts\PaymentMeansCast;
use Moox\Invoice\Support\En16931\Party;
use Moox\Invoice\Support\En16931\PaymentMeans;
use Moox\Invoice\Support\InvoiceModels;

/**
 * @property Party|null $seller
 * @property Party|null $buyer
 * @property Address|null $delivery
 * @property PaymentMeans|null $payment_means
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
        'due_date',
        'currency',
        'customer_reference',
        'order_number',
        'order_date',
        'pricing_basis',
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
            'delivery' => AddressCast::class,
            'payment_means' => PaymentMeansCast::class,
            'net_total' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_total' => 'decimal:2',
        ];
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
