<?php

declare(strict_types=1);

namespace Moox\Staff\Support;

final class StaffRules
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', config('staff.statuses', ['draft']))],
            'legacy_id' => ['nullable', 'integer', 'min:1'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'short_code' => ['nullable', 'string', 'max:20'],
            'display_name' => ['nullable', 'string', 'max:160'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'language_id' => ['nullable', 'integer', 'exists:static_languages,id'],
            'contact_id' => ['nullable', 'uuid'],
            'is_internal' => ['boolean'],
            'data' => ['nullable', 'array'],
        ];
    }

    /** @return list<string> */
    public static function for(string $field): array
    {
        return self::rules()[$field] ?? [];
    }
}
