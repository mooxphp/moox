<?php

declare(strict_types=1);

namespace Moox\Address\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class AddressRules
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public static function rules(): array
    {
        return [
            'is_active' => ['boolean'],
            'street' => ['required', 'string', 'max:255'],
            'house_number' => ['required', 'string', 'max:255'],
            'address_line_2' => ['required', 'string', 'max:255'],
            'po_box' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'external_info' => ['nullable', 'string', 'max:255'],
            'external_status' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['required', 'integer'],
            'owner_type' => ['required', 'string', 'max:255'],
            'country_id' => ['nullable', 'integer'],
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
                            $fail(__('address::fields.data_invalid'));
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
