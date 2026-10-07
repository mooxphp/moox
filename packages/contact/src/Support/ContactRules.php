<?php

declare(strict_types=1);

namespace Moox\Contact\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ContactRules
{
    /**
     * @return array<string, list<string|ValidationRule>>
     */
    public static function rules(): array
    {
        return [
            'is_active' => ['boolean'],
            'name_1' => ['required', 'string', 'max:255'],
            'name_2' => ['nullable', 'string', 'max:255'],
            'name_3' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'external_info' => ['nullable', 'string', 'max:255'],
            'external_status' => ['nullable', 'string', 'max:255'],
            'language_id' => ['nullable', 'integer'],
            'country_id' => ['nullable', 'integer'],
            'organization_type_id' => ['nullable', 'integer'],
            'legal_form_id' => ['nullable', 'integer'],
            'data_json' => [
                'nullable',
                new class implements ValidationRule
                {
                    public function validate(string $attribute, mixed $value, Closure $fail): void
                    {
                        if ($value === null || $value === '' || is_array($value)) {
                            return;
                        }

                        if (! is_string($value) || ! json_validate($value)) {
                            $fail(__('contact::fields.data_json_invalid'));
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
