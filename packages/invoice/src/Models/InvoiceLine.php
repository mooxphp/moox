<?php

declare(strict_types=1);

namespace Moox\Invoice\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Moox\Core\Entities\Items\Item\BaseItemModel;
use Moox\Invoice\Database\Factories\InvoiceLineFactory;
use Moox\Invoice\Support\En16931\Address;
use Moox\Invoice\Support\En16931\Casts\AddressCast;
use Moox\Invoice\Support\InvoiceModels;

/**
 * @property Address|null $delivery
 */
class InvoiceLine extends BaseItemModel
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'invoice_id',
        'position',
        'unit',
        'quantity',
        'description',
        'description_detail',
        'article_number',
        'customs_tariff_number',
        'unit_price',
        'line_total',
        'delivery',
        'delivery_date',
        'delivery_note_number',
        'order_number',
        'order_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery' => AddressCast::class,
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    public static function getResourceName(): string
    {
        return 'invoice_line';
    }

    public static function newFactory(): InvoiceLineFactory
    {
        return InvoiceLineFactory::new();
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModels::invoice());
    }

    /**
     * @return MorphMany<InvoiceAllowanceCharge, $this>
     */
    public function allowanceCharges(): MorphMany
    {
        return $this->morphMany(InvoiceModels::invoiceAllowanceCharge(), 'chargeable');
    }
}
