<?php

declare(strict_types=1);

namespace Moox\EBilling\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int|null $material_id
 * @property array<string, mixed>|null $field_validations
 */
class InvoiceLine extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_lines';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'material_id',
        'position',
        'unit',
        'quantity',
        'description',
        'description_detail',
        'article_number',
        'material',
        'material_test_certificate',
        'material_test_certificate_price',
        'customs_tariff_number',
        'weight_kg_total',
        'weight_kg_net',
        'unit_price',
        'line_total',
        'surcharge_amount',
        'surcharge_description',
        'delivery_date',
        'delivery_note_number',
        'order_number',
        'order_date',
        'delivery_address',
        'field_validations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivery_address' => 'array',
            'field_validations' => 'array',
            'quantity' => 'decimal:3',
            'weight_kg_total' => 'decimal:3',
            'weight_kg_net' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'material_test_certificate_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function materialRecord(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'material_id');
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
}
