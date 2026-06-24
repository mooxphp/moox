<?php

declare(strict_types=1);

namespace Moox\Supplier\Support;

final class SupplierRules
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', config('supplier.statuses', ['draft']))],
            'supplier_number' => ['nullable', 'string', 'max:40'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'search_terms' => ['nullable', 'string'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lead_time_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'minimum_order_value' => ['nullable', 'numeric', 'min:0'],
            'language_id' => ['nullable', 'integer', 'exists:static_languages,id'],
            'is_preferred' => ['boolean'],
            'note' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'approved_at' => ['nullable', 'date'],
            'data' => ['nullable', 'array'],
        ];
    }

    /** @return list<string> */
    public static function for(string $field): array
    {
        return self::rules()[$field] ?? [];
    }
}
