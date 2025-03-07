<?php

declare(strict_types=1);

namespace Moox\Connect\Transformers;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Moox\Connect\Exceptions\TransformerException;

final class DateTimeTransformer extends BaseTransformer
{
    public function transform(mixed $value, array $options = []): string
    {
        if (is_string($value)) {
            $value = $this->parseDateTime($value);
        }

        if (! $value instanceof DateTimeInterface) {
            throw TransformerException::transformationFailed(
                $this->getName(),
                'Cannot transform value of type '.get_debug_type($value).' to datetime'
            );
        }

        $format = $options['format'] ?? 'c';

        return $value->format($format);
    }

    public function reverseTransform(mixed $value, array $options = []): CarbonInterface
    {
        if (! is_string($value)) {
            throw TransformerException::invalidType(
                $this->getName(),
                'string',
                get_debug_type($value)
            );
        }

        return $this->parseDateTime($value);
    }

    public function supports(mixed $value, array $options = []): bool
    {
        if ($value instanceof DateTimeInterface) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        try {
            $this->parseDateTime($value);

            return true;
        } catch (TransformerException) {
            return false;
        }
    }

    private function parseDateTime(string $value): CarbonInterface
    {
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            throw TransformerException::transformationFailed(
                $this->getName(),
                "Failed to parse datetime: {$e->getMessage()}"
            );
        }
    }

    protected function getRequiredOptions(): array
    {
        return [];
    }
}
