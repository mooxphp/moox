<?php

declare(strict_types=1);

namespace Moox\Connect\Transformers;

use Moox\Connect\Exceptions\TransformerException;

final class ArrayTransformer extends BaseTransformer
{
    public function transform(mixed $value, array $options = []): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            if (method_exists($value, 'toArray')) {
                return $value->toArray();
            }

            return (array) $value;
        }

        if (is_scalar($value) || is_null($value)) {
            return [$value];
        }

        throw TransformerException::transformationFailed(
            $this->getName(),
            'Cannot transform value of type '.get_debug_type($value).' to array'
        );
    }

    public function reverseTransform(mixed $value, array $options = []): mixed
    {
        if (! is_array($value)) {
            throw TransformerException::invalidType(
                $this->getName(),
                'array',
                get_debug_type($value)
            );
        }

        $type = $options['target_type'] ?? 'array';

        return match ($type) {
            'array' => $value,
            'object' => (object) $value,
            'collection' => collect($value),
            default => throw TransformerException::invalidOption($this->getName(), 'target_type')
        };
    }

    public function supports(mixed $value, array $options = []): bool
    {
        if (is_array($value)) {
            return true;
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            return true;
        }

        return is_scalar($value) || is_null($value);
    }

    protected function getRequiredOptions(): array
    {
        return [];
    }
}
