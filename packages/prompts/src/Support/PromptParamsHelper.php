<?php

namespace Moox\Prompts\Support;

/**
 * Helper class for accessing prompt parameters.
 *
 * Instead of directly accessing $params[0], $params[1], etc.,
 * we use these helper methods that know the parameter names.
 *
 * This makes us more robust against changes in Laravel Prompts,
 * as long as the parameter names remain the same.
 */
class PromptParamsHelper
{
    /**
     * Extracts the parameters for a prompt method as an associative array.
     *
     * @param  string  $method  The prompt method (e.g. 'text', 'confirm', 'select')
     * @param  array  $params  The numeric parameter array
     * @return array Associative array with parameter names as keys
     */
    public static function extract(string $method, array $params): array
    {
        return match ($method) {
            'text' => [
                'label' => $params[0] ?? '',
                'placeholder' => $params[1] ?? '',
                'default' => $params[2] ?? null,
                'required' => $params[3] ?? false,
                'validate' => $params[4] ?? null,
                'hint' => $params[5] ?? '',
                'transform' => $params[6] ?? null,
            ],
            'textarea' => [
                'label' => $params[0] ?? '',
                'placeholder' => $params[1] ?? '',
                'required' => $params[2] ?? false,
                'validate' => $params[3] ?? null,
                'hint' => $params[4] ?? '',
                'transform' => $params[5] ?? null,
            ],
            'password' => [
                'label' => $params[0] ?? '',
                'placeholder' => $params[1] ?? '',
                'required' => $params[2] ?? false,
                'validate' => $params[3] ?? null,
                'hint' => $params[4] ?? '',
                'transform' => $params[5] ?? null,
            ],
            'confirm' => [
                'label' => $params[0] ?? '',
                'default' => $params[1] ?? false,
                'required' => $params[2] ?? false,
                'yes' => $params[3] ?? 'I accept',
                'no' => $params[4] ?? 'I decline',
                'hint' => $params[5] ?? '',
            ],
            'select' => [
                'label' => $params[0] ?? '',
                'options' => $params[1] ?? [],
                'default' => $params[2] ?? null,
                'scroll' => $params[3] ?? null,
                'hint' => $params[4] ?? '',
                'validate' => $params[5] ?? null,
                'transform' => $params[6] ?? null,
            ],
            'multiselect' => [
                'label' => $params[0] ?? '',
                'options' => $params[1] ?? [],
                'default' => $params[2] ?? [],
                'required' => $params[3] ?? false,
                'scroll' => $params[4] ?? null,
                'hint' => $params[5] ?? '',
                'validate' => $params[6] ?? null,
                'transform' => $params[7] ?? null,
            ],
            default => [],
        };
    }

    /**
     * Returns a single parameter.
     *
     * @param  string  $method  The prompt method
     * @param  array  $params  The numeric parameter array
     * @param  string  $paramName  The name of the parameter (e.g. 'label', 'default')
     * @param  mixed  $default  The default value if the parameter does not exist
     */
    public static function get(string $method, array $params, string $paramName, mixed $default = null): mixed
    {
        $extracted = self::extract($method, $params);

        return $extracted[$paramName] ?? $default;
    }
}
