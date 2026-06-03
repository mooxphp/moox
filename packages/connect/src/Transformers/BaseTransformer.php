<?php

declare(strict_types=1);

namespace Moox\Connect\Transformers;

use Moox\Connect\Contracts\TransformerInterface;
use Moox\Connect\Exceptions\TransformerException;

abstract class BaseTransformer implements TransformerInterface
{
    private string $name;

    private array $defaultOptions;

    public function __construct(
        string $name,
        int $priority = 0,
        array $defaultOptions = []
    ) {
        $this->name = $name;
        $this->priority = $priority;
        $this->defaultOptions = $defaultOptions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    protected function mergeOptions(array $options): array
    {
        $mergedOptions = array_merge($this->defaultOptions, $options);
        $this->validateOptions($mergedOptions);

        return $mergedOptions;
    }

    public function validateOptions(array $options): void
    {
        foreach ($this->getRequiredOptions() as $option) {
            if (! isset($options[$option])) {
                throw TransformerException::missingOption($this->name, $option);
            }
        }
    }

    protected function getRequiredOptions(): array
    {
        return [];
    }

    public function withDefaultOptions(array $options): self
    {
        $clone = clone $this;
        $clone->defaultOptions = array_merge($this->defaultOptions, $options);
        $clone->validateOptions($clone->defaultOptions);

        return $clone;
    }

    protected function assertType(mixed $value, string $type): void
    {
        $actualType = get_debug_type($value);

        if ($type === 'numeric' && is_numeric($value)) {
            return;
        }

        if ($type === 'scalar' && is_scalar($value)) {
            return;
        }

        if ($type === 'arrayable' &&
            (is_array($value) || (is_object($value) && method_exists($value, 'toArray')))
        ) {
            return;
        }

        if ($actualType !== $type) {
            throw TransformerException::invalidType($this->name, $type, $actualType);
        }
    }

    protected function assertArrayType(array $value, string $type): void
    {
        foreach ($value as $index => $item) {
            try {
                $this->assertType($item, $type);
            } catch (TransformerException $e) {
                throw TransformerException::transformationFailed(
                    $this->name,
                    "Array item at index {$index} has invalid type: {$e->getMessage()}"
                );
            }
        }
    }

    protected function assertOptionType(array $options, string $key, string $type): void
    {
        if (! isset($options[$key])) {
            return;
        }

        try {
            $this->assertType($options[$key], $type);
        } catch (TransformerException $e) {
            throw TransformerException::invalidOption($this->name, $key);
        }
    }
}
