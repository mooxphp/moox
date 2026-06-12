<?php

declare(strict_types=1);

namespace Moox\Invoice\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Moox\Invoice\Database\Factories\InvoiceAllowanceChargeFactory;

class InvoiceAllowanceCharge extends Model
{
    /** @use HasFactory<InvoiceAllowanceChargeFactory> */
    use HasFactory;

    protected $fillable = [
        'is_charge',
        'amount',
        'reason_code',
        'reason_text',
        'base_amount',
        'percentage',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_charge' => 'boolean',
            'amount' => 'decimal:2',
            'base_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
        ];
    }

    public static function newFactory(): InvoiceAllowanceChargeFactory
    {
        return InvoiceAllowanceChargeFactory::new();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function chargeable(): MorphTo
    {
        return $this->morphTo();
    }
}
