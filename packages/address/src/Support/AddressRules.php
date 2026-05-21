<?php

declare(strict_types=1);

namespace Moox\Address\Support;

final class AddressRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:160'],
            'street' => ['required', 'string', 'max:160'],
            'street2' => ['nullable', 'string', 'max:160'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country_code' => ['required', 'string', 'size:2', 'alpha:ascii', 'uppercase'],
            'is_primary' => ['boolean'],
            'data' => ['nullable', 'array'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function for(string $field): array
    {
        return self::rules()[$field] ?? [];
    }
}
