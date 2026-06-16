<?php

declare(strict_types=1);

namespace Moox\Audit\Support;

final class AuditConfigMerger
{
    /** @var list<string> */
    private const LIST_REPLACE_KEYS = [
        'attributes',
        'hidden_attributes',
        'events',
        'properties',
        'aggregate_subjects',
    ];

    /** @var array<string, string> */
    private const LIST_APPEND_MAP = [
        'append_attributes' => 'attributes',
        'append_hidden_attributes' => 'hidden_attributes',
        'append_properties' => 'properties',
    ];

    /**
     * @param  array<string, mixed>  $preset
     * @param  array<string, mixed>  $package
     * @param  array<string, mixed>  $app
     * @return array<string, mixed>
     */
    public static function merge(array $preset, array $package, array $app): array
    {
        $merged = array_replace_recursive($preset, $package);

        foreach ($app as $key => $value) {
            if (array_key_exists($key, self::LIST_APPEND_MAP)) {
                continue;
            }

            if (in_array($key, self::LIST_REPLACE_KEYS, true) && is_array($value)) {
                $merged[$key] = array_values($value);

                continue;
            }

            $merged[$key] = $value;
        }

        foreach (self::LIST_APPEND_MAP as $appendKey => $targetKey) {
            if (! isset($app[$appendKey]) || ! is_array($app[$appendKey])) {
                continue;
            }

            $existing = is_array($merged[$targetKey] ?? null) ? $merged[$targetKey] : [];

            $merged[$targetKey] = array_values(array_unique([
                ...$existing,
                ...$app[$appendKey],
            ]));
        }

        return $merged;
    }
}
