<?php

declare(strict_types=1);

namespace Moox\Connect\Transformers;

use Moox\Connect\Exceptions\TransformerException;

final class NumberTransformer extends BaseTransformer
{
    public function transform(mixed $value, array $options = []): float|int
    {
        if (is_numeric($value)) {
            return $this->formatNumber($value, $options);
        }

        if (is_string($value)) {
            $value = $this->parseNumber($value);

            return $this->formatNumber($value, $options);
        }

        throw TransformerException::transformationFailed(
            $this->getName(),
            'Cannot transform value of type '.get_debug_type($value).' to number'
        );
    }

    public function reverseTransform(mixed $value, array $options = []): string
    {
        if (! is_numeric($value)) {
            throw TransformerException::invalidType(
                $this->getName(),
                'numeric',
                get_debug_type($value)
            );
        }

        $locale = $options['locale'] ?? null;
        $decimals = $options['decimals'] ?? null;
        $decPoint = $options['decimal_point'] ?? null;
        $thousandsSep = $options['thousands_separator'] ?? null;

        if ($locale !== null) {
            $formatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
            if ($decimals !== null) {
                $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
            }

            return $formatter->format($value);
        }

        if ($decimals !== null) {
            return number_format(
                $value,
                $decimals,
                $decPoint ?? '.',
                $thousandsSep ?? ''
            );
        }

        return (string) $value;
    }

    public function supports(mixed $value, array $options = []): bool
    {
        if (is_numeric($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        try {
            $this->parseNumber($value);

            return true;
        } catch (TransformerException) {
            return false;
        }
    }

    private function parseNumber(string $value): float|int
    {
        $value = trim($value);

        if (preg_match('/^-?\d*\.?\d+$/', $value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        throw TransformerException::transformationFailed(
            $this->getName(),
            "Invalid number format: {$value}"
        );
    }

    private function formatNumber(float|int $value, array $options): float|int
    {
        $precision = $options['precision'] ?? null;

        if ($precision !== null) {
            return round($value, $precision);
        }

        return $value;
    }

    protected function getRequiredOptions(): array
    {
        return [];
    }
}
