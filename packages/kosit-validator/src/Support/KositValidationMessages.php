<?php

declare(strict_types=1);

namespace Moox\KositValidator\Support;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Moox\KositValidator\Models\KositValidation;

/**
 * Normalization helpers for {@see KositValidation::$errors} JSON.
 */
final class KositValidationMessages
{
    /**
     * @return list<array{type: string, text: string, location: string|null, rule: string|null}>
     */
    public static function normalized(?array $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_string($item)) {
                $trimmed = trim($item);
                if ($trimmed !== '') {
                    $out[] = [
                        'type' => 'error',
                        'text' => $trimmed,
                        'location' => null,
                        'rule' => null,
                    ];
                }

                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $type = is_string($item['type'] ?? null) ? $item['type'] : 'error';
            $text = is_string($item['text'] ?? null) ? trim($item['text']) : '';
            if ($text === '') {
                continue;
            }

            $location = $item['location'] ?? null;
            $rule = $item['rule'] ?? null;

            $out[] = [
                'type' => $type,
                'text' => $text,
                'location' => is_string($location) && $location !== '' ? $location : null,
                'rule' => is_string($rule) && $rule !== '' ? $rule : null,
            ];
        }

        return $out;
    }

    /**
     * @return array{error: int, warning: int, info: int}
     */
    public static function counts(?array $raw): array
    {
        $messages = self::normalized($raw);
        $counts = ['error' => 0, 'warning' => 0, 'info' => 0];
        foreach ($messages as $message) {
            if (isset($counts[$message['type']])) {
                $counts[$message['type']]++;
            }
        }

        return $counts;
    }

    /**
     * @param  Builder<KositValidation>  $query
     * @return Builder<KositValidation>
     */
    public static function applyHasMessageType(Builder $query, string $type): Builder
    {
        $driver = self::queryDriverName($query);

        return match ($driver) {
            'sqlite' => $query->whereRaw(
                'EXISTS (
                    SELECT 1 FROM json_each(COALESCE(errors, ?)) AS e
                    WHERE json_extract(e.value, \'$.type\') = ?
                    OR (? = \'error\' AND json_type(e.value) = \'text\')
                )',
                ['[]', $type, $type]
            ),
            'mysql' => $query->whereRaw(
                '(JSON_SEARCH(errors, \'one\', ?, NULL, \'$[*].type\') IS NOT NULL
                OR (? = \'error\' AND JSON_TYPE(JSON_EXTRACT(errors, \'$[0]\')) = \'STRING\'
                    AND JSON_LENGTH(errors) > 0))',
                [$type, $type]
            ),
            default => $query->whereRaw('CAST(errors AS TEXT) LIKE ?', ['%"type":"'.$type.'"%']),
        };
    }

    /**
     * @param  Builder<KositValidation>  $query
     * @return Builder<KositValidation>
     */
    public static function applyErrorsTextSearch(Builder $query, string $search): Builder
    {
        $like = '%'.$search.'%';
        $driver = self::queryDriverName($query);

        return match ($driver) {
            'sqlite' => $query->orWhereRaw(
                'EXISTS (
                    SELECT 1 FROM json_each(COALESCE(errors, ?)) AS e
                    WHERE json_extract(e.value, \'$.text\') LIKE ?
                    OR (json_type(e.value) = \'text\' AND e.value LIKE ?)
                )',
                ['[]', $like, $like]
            ),
            'mysql' => $query->orWhereRaw(
                "(JSON_SEARCH(errors, 'one', ?, NULL, '\$[*].text') IS NOT NULL
                OR CAST(errors AS CHAR) LIKE ?)",
                [$search, $like]
            ),
            default => $query->orWhereRaw('CAST(errors AS TEXT) LIKE ?', [$like]),
        };
    }

    /**
     * @param  Builder<KositValidation>  $query
     */
    private static function queryDriverName(Builder $query): string
    {
        $connection = $query->getConnection();

        return $connection instanceof Connection
            ? $connection->getDriverName()
            : 'unknown';
    }
}
