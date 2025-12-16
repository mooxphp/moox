<?php

namespace Moox\Prompts\Support;

/**
 * Helper-Klasse zum Zugriff auf Prompt-Parameter.
 *
 * Statt direkt auf $params[0], $params[1] etc. zuzugreifen,
 * verwenden wir diese Helper-Methoden, die die Parameter-Namen kennen.
 *
 * So sind wir robuster gegen Änderungen in Laravel Prompts,
 * solange die Parameter-Namen gleich bleiben.
 */
class PromptParamsHelper
{
    /**
     * Extrahiert die Parameter für eine Prompt-Methode als assoziatives Array.
     *
     * @param  string  $method  Die Prompt-Methode (z.B. 'text', 'confirm', 'select')
     * @param  array  $params  Die numerischen Parameter-Array
     * @return array Assoziatives Array mit Parameternamen als Keys
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
     * Gibt einen einzelnen Parameter zurück.
     *
     * @param  string  $method  Die Prompt-Methode
     * @param  array  $params  Die numerischen Parameter-Array
     * @param  string  $paramName  Der Name des Parameters (z.B. 'label', 'default')
     * @param  mixed  $default  Der Default-Wert, falls der Parameter nicht existiert
     */
    public static function get(string $method, array $params, string $paramName, mixed $default = null): mixed
    {
        $extracted = self::extract($method, $params);

        return $extracted[$paramName] ?? $default;
    }
}
