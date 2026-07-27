<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

final class ContactRules
{
    /** @return array<string, list<string>> */
    public static function rules(): array
    {
        return [
            'status' => ['required', 'string', 'max:30', 'in:'.implode(',', config('contact.statuses', ['draft']))],
            'gender' => ['nullable', 'string', 'max:20', 'in:'.implode(',', config('contact.genders', ['unknown']))],
            'salutation_code' => ['nullable', 'string', 'max:30'],
            'academic_title' => ['nullable', 'string', 'max:80'],
            'first_name' => ['nullable', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'display_name' => ['nullable', 'string', 'max:160'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'max:120', 'email'],
            'username' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:30'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'language_id' => ['nullable', 'integer', 'exists:static_languages,id'],
            'contact_type' => ['required', 'string', 'max:30', 'in:'.implode(',', config('contact.contact_types', ['external']))],
            'note' => ['nullable', 'string'],
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
