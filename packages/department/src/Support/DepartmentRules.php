<?php

declare(strict_types=1);

namespace Moox\Department\Support;

final class DepartmentRules
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', config('department.statuses', ['draft']))],
            'name' => ['required', 'string', 'max:160'],
            'code' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'data' => ['nullable', 'array'],
        ];
    }

    /** @return list<string> */
    public static function for(string $field): array
    {
        return self::rules()[$field] ?? [];
    }
}
