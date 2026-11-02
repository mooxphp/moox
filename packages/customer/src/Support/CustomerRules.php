<?php

declare(strict_types=1);

namespace Moox\Customer\Support;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

final class CustomerRules
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public static function rules(): array
    {
        return [
            'is_active' => ['boolean'],
            'customer_type' => ['required', 'string', Rule::in(['B2B', 'B2C', 'B2D', 'B2G'])],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_number' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'external_info' => ['nullable', 'string', 'max:255'],
            'external_status' => ['nullable', 'string', 'max:255'],
            'contact_id' => ['required', 'integer'],
            'language_id' => ['required', 'integer'],
        ];
    }

    /**
     * @return list<string|ValidationRule>
     */
    public static function for(string $field): array
    {
        return self::rules()[$field] ?? [];
    }
}
