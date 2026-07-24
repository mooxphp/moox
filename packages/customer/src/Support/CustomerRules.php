<?php

declare(strict_types=1);

namespace Moox\Customer\Support;

final class CustomerRules
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', config('customer.statuses', ['draft']))],
            'customer_number' => ['nullable', 'string', 'max:40'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'customer_name' => ['nullable', 'string', 'max:160'],
            'search_terms' => ['nullable', 'string'],
            'price_type' => ['nullable', 'string', 'max:30', 'in:'.implode(',', config('customer.price_types', []))],
            'customer_group' => ['nullable', 'string', 'max:50'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'language_id' => ['nullable', 'integer', 'exists:static_languages,id'],
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
