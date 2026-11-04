<?php

declare(strict_types=1);

namespace Moox\Transform\Support;

final class MappedExpression
{
    /**
     * Resolve the executable source expression from a stored mapping value.
     *
     * Stored form is either a string path, or {expression, note} when documented.
     */
    public static function path(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (! is_array($value)) {
            return null;
        }

        $expression = $value['expression'] ?? null;

        return is_string($expression) && $expression !== '' ? $expression : null;
    }

    public static function note(mixed $value): ?string
    {
        if (! is_array($value)) {
            return null;
        }

        $note = $value['note'] ?? null;
        if (! is_string($note)) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }

    /**
     * @return string|array{expression: string, note: string}
     */
    public static function store(string $expression, ?string $note = null): string|array
    {
        $trimmedNote = is_string($note) ? trim($note) : '';

        if ($trimmedNote === '') {
            return $expression;
        }

        return [
            'expression' => $expression,
            'note' => $trimmedNote,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return string|array{expression: string, note: string}|null
     */
    public static function fromFormRow(array $row): string|array|null
    {
        $sourcePath = $row['source_path'] ?? null;
        if (! is_string($sourcePath) || $sourcePath === '') {
            return null;
        }

        $note = $row['note'] ?? null;

        return self::store($sourcePath, is_string($note) ? $note : null);
    }

    /**
     * @return array{source_path: string, note: string}|null
     */
    public static function toFormRow(mixed $value): ?array
    {
        $path = self::path($value);
        if ($path === null) {
            return null;
        }

        return [
            'source_path' => $path,
            'note' => self::note($value) ?? '',
        ];
    }
}
