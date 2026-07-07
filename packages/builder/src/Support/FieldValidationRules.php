<?php

declare(strict_types=1);

namespace Moox\Builder\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Moox\Builder\Data\FieldDefinition;

final class FieldValidationRules
{
    /**
     * @return array<string, string>
     */
    public function availableRulesForType(?string $type): array
    {
        return match ($this->categoryForType($type)) {
            'text' => [
                'min' => __('builder::builder.field.validation_rule_min'),
                'max' => __('builder::builder.field.validation_rule_max'),
                'regex' => __('builder::builder.field.validation_rule_regex'),
                'alpha_dash' => __('builder::builder.field.validation_rule_alpha_dash'),
                'alpha_num' => __('builder::builder.field.validation_rule_alpha_num'),
            ],
            'numeric' => [
                'min' => __('builder::builder.field.validation_rule_min'),
                'max' => __('builder::builder.field.validation_rule_max'),
                'gt' => __('builder::builder.field.validation_rule_gt'),
                'gte' => __('builder::builder.field.validation_rule_gte'),
                'lt' => __('builder::builder.field.validation_rule_lt'),
                'lte' => __('builder::builder.field.validation_rule_lte'),
            ],
            default => [],
        };
    }

    public function supportsType(?string $type): bool
    {
        return $this->availableRulesForType($type) !== [];
    }

    /**
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    public function formStateFor(array $validation, ?string $type): array
    {
        $supportedRules = array_keys($this->availableRulesForType($type));
        $rows = [];
        $rawRules = [];

        foreach ($this->normalizeRules($validation['rules'] ?? []) as $rule) {
            $parsed = $this->parseRule($rule, $supportedRules);

            if ($parsed === null) {
                $rawRules[] = $rule;

                continue;
            }

            $rows[] = $parsed;
        }

        $validation['rule_rows'] = $rows;
        $validation['raw_rules'] = implode(PHP_EOL, $rawRules);

        return $validation;
    }

    /**
     * @param  array<string, mixed>  $validation
     * @return array<string, mixed>
     */
    public function compileValidation(array $validation, ?string $type): array
    {
        if (! $this->supportsType($type)) {
            return [
                'required' => (bool) ($validation['required'] ?? false),
                'rules' => $this->normalizeRules($validation['rules'] ?? []),
            ];
        }

        $supportedRules = array_keys($this->availableRulesForType($type));
        $rules = [];

        foreach ($this->normalizeRuleRows($validation['rule_rows'] ?? []) as $row) {
            $rule = (string) ($row['rule'] ?? '');

            if (! in_array($rule, $supportedRules, true)) {
                continue;
            }

            if ($this->ruleNeedsValue($rule)) {
                $value = trim((string) ($row['value'] ?? ''));

                if ($value === '') {
                    continue;
                }

                $rules[] = "{$rule}:{$value}";

                continue;
            }

            $rules[] = $rule;
        }

        foreach ($this->rawRulesFromText($validation['raw_rules'] ?? null) as $rule) {
            $rules[] = $rule;
        }

        return [
            'required' => (bool) ($validation['required'] ?? false),
            'rules' => array_values(array_unique($rules)),
        ];
    }

    /**
     * @return list<string>
     */
    public function runtimeRulesFor(FieldDefinition $field): array
    {
        if (! $this->supportsType($field->type)) {
            return [];
        }

        return $this->normalizeRules($field->validation['rules'] ?? []);
    }

    /**
     * @return array{type: string, value: mixed}|null
     */
    public function validatorContextForType(string $type, mixed $value): ?array
    {
        return match ($this->categoryForType($type)) {
            'text' => ['type' => 'string', 'value' => is_string($value) ? $value : (string) $value],
            'numeric' => ['type' => 'numeric', 'value' => $value],
            default => null,
        };
    }

    public function ruleNeedsValue(string $rule): bool
    {
        return ! in_array($rule, ['alpha_dash', 'alpha_num'], true);
    }

    /**
     * @param  mixed  $rules
     * @return list<string>
     */
    protected function normalizeRules(mixed $rules): array
    {
        if (! is_array($rules)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $rule): string => trim((string) $rule), $rules),
            static fn (string $rule): bool => $rule !== '',
        ));
    }

    /**
     * @param  mixed  $rows
     * @return list<array{rule: string, value?: string}>
     */
    protected function normalizeRuleRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static function (mixed $row): ?array {
                if (! is_array($row)) {
                    return null;
                }

                $rule = trim((string) ($row['rule'] ?? ''));

                if ($rule === '') {
                    return null;
                }

                return [
                    'rule' => $rule,
                    'value' => Arr::get($row, 'value'),
                ];
            }, $rows),
        ));
    }

    /**
     * @param  list<string>  $supportedRules
     * @return array{rule: string, value?: string}|null
     */
    protected function parseRule(string $rule, array $supportedRules): ?array
    {
        if (in_array($rule, ['alpha_dash', 'alpha_num'], true) && in_array($rule, $supportedRules, true)) {
            return ['rule' => $rule];
        }

        [$name, $value] = array_pad(explode(':', $rule, 2), 2, null);

        if (! in_array($name, $supportedRules, true) || $value === null) {
            return null;
        }

        return [
            'rule' => $name,
            'value' => $value,
        ];
    }

    /**
     * @return list<string>
     */
    protected function rawRulesFromText(mixed $rawRules): array
    {
        if (! is_string($rawRules) || trim($rawRules) === '') {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn (string $rule): string => trim($rule), preg_split('/\r\n|\r|\n/', $rawRules) ?: []),
            static fn (string $rule): bool => $rule !== '',
        ));
    }

    protected function categoryForType(?string $type): ?string
    {
        return match ($type) {
            'text', 'textarea', 'email', 'password', 'url', 'oembed', 'rich_text' => 'text',
            'number', 'range' => 'numeric',
            default => null,
        };
    }
}
