<?php

declare(strict_types=1);

namespace Moox\Company\Support;

final class CompanyRules
{
    /**
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', config('company.statuses', ['draft']))],
            'name' => ['nullable', 'string', 'max:120'],
            'display_name' => ['nullable', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'uuid', 'exists:companies,id'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'fax' => ['nullable', 'string', 'max:30'],
            'url' => ['nullable', 'string', 'max:255', 'url'],
            'email' => ['nullable', 'string', 'max:100', 'email'],
            'tax_number' => ['nullable', 'string', 'max:30'],
            'vat_number' => ['nullable', 'string', 'max:30'],
            'default_currency_code' => ['required', 'string', 'size:3', 'alpha:ascii', 'uppercase'],
            'language_id' => ['nullable', 'integer', 'exists:static_languages,id'],
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
