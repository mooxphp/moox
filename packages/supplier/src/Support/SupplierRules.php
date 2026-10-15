<?php

declare(strict_types=1);

namespace Moox\Supplier\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

final class SupplierRules
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public static function rules(): array
    {
        return [
            'is_active' => ['boolean'],
            'supplier_type' => ['required', 'string', Rule::in(['Producer', 'Distributor'])],
            'supplier_name' => ['required', 'string', 'max:255'],
            'supplier_number' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'external_info' => ['nullable', 'string', 'max:255'],
            'external_status' => ['nullable', 'string', 'max:255'],
            'contact_id' => ['required', 'integer'],
            'language_id' => ['required', 'integer'],
            'data' => [
                'nullable',
                new class implements ValidationRule
                {
                    public function validate(string $attribute, mixed $value, Closure $fail): void
                    {
                        if ($value === null || $value === '' || is_array($value)) {
                            return;
                        }

                        if (! is_string($value) || ! json_validate($value)) {
                            $fail(__('supplier::fields.data_invalid'));
                        }
                    }
                },
            ],
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
