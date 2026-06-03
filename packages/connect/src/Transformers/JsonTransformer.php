<?php

declare(strict_types=1);

namespace Moox\Connect\Transformers;

use JsonException;
use Moox\Connect\Exceptions\TransformerException;

final class JsonTransformer extends BaseTransformer
{
    public function transform(mixed $value, array $options = []): string
    {
        if (is_string($value) && $this->isJson($value)) {
            return $value;
        }

        try {
            $flags = $options['encode_flags'] ?? JSON_THROW_ON_ERROR;

            return json_encode($value, $flags);
        } catch (JsonException $e) {
            throw TransformerException::transformationFailed(
                $this->getName(),
                "Failed to encode JSON: {$e->getMessage()}"
            );
        }
    }

    public function reverseTransform(mixed $value, array $options = []): mixed
    {
        if (! is_string($value)) {
            throw TransformerException::invalidType(
                $this->getName(),
                'string',
                get_debug_type($value)
            );
        }

        try {
            $flags = $options['decode_flags'] ?? JSON_THROW_ON_ERROR;
            $assoc = $options['assoc'] ?? true;

            return json_decode($value, $assoc, 512, $flags);
        } catch (JsonException $e) {
            throw TransformerException::transformationFailed(
                $this->getName(),
                "Failed to decode JSON: {$e->getMessage()}"
            );
        }
    }

    public function supports(mixed $value, array $options = []): bool
    {
        if (is_string($value)) {
            return $this->isJson($value);
        }

        return is_array($value) || is_object($value) || is_scalar($value) || is_null($value);
    }

    private function isJson(string $value): bool
    {
        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            return true;
        } catch (JsonException) {
            return false;
        }
    }

    protected function getRequiredOptions(): array
    {
        return [];
    }
}
