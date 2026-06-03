<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Moox\Connect\Contracts\TransformerInterface;
use Moox\Connect\Exceptions\TransformerException;

final class TransformerRegistry
{
    private array $transformers = [];

    private array $transformersByName = [];

    private bool $sorted = true;

    public function register(TransformerInterface $transformer): void
    {
        $name = $transformer->getName();

        if (isset($this->transformersByName[$name])) {
            throw TransformerException::transformationFailed(
                $name,
                'Transformer already registered'
            );
        }

        $this->transformers[] = $transformer;
        $this->transformersByName[$name] = $transformer;
        $this->sorted = false;
    }

    public function unregister(string $name): void
    {
        if (! isset($this->transformersByName[$name])) {
            return;
        }

        $transformer = $this->transformersByName[$name];
        unset($this->transformersByName[$name]);

        $index = array_search($transformer, $this->transformers, true);
        if ($index !== false) {
            unset($this->transformers[$index]);
            $this->transformers = array_values($this->transformers);
        }
    }

    public function get(string $name): TransformerInterface
    {
        if (! isset($this->transformersByName[$name])) {
            throw TransformerException::transformationFailed(
                $name,
                'Transformer not found'
            );
        }

        return $this->transformersByName[$name];
    }

    public function findForValue(mixed $value, array $options = []): ?TransformerInterface
    {
        $this->sortTransformers();

        foreach ($this->transformers as $transformer) {
            if ($transformer->supports($value, $options)) {
                return $transformer;
            }
        }

        return null;
    }

    public function transform(mixed $value, array $options = [], ?string $transformerName = null): mixed
    {
        $transformer = $transformerName !== null
            ? $this->get($transformerName)
            : $this->findForValue($value, $options);

        if ($transformer === null) {
            throw TransformerException::transformationFailed(
                'registry',
                'No suitable transformer found for value of type '.get_debug_type($value)
            );
        }

        return $transformer->transform($value, $options);
    }

    public function reverseTransform(mixed $value, array $options, string $transformerName): mixed
    {
        $transformer = $this->get($transformerName);

        return $transformer->reverseTransform($value, $options);
    }

    public function getAll(): array
    {
        $this->sortTransformers();

        return $this->transformers;
    }

    public function clear(): void
    {
        $this->transformers = [];
        $this->transformersByName = [];
        $this->sorted = true;
    }

    private function sortTransformers(): void
    {
        if ($this->sorted) {
            return;
        }

        $this->transformers = array_values($this->transformers);
        $this->sorted = true;
    }

    public function withTransformer(TransformerInterface $transformer): self
    {
        $clone = clone $this;
        $clone->register($transformer);

        return $clone;
    }

    public function withoutTransformer(string $name): self
    {
        $clone = clone $this;
        $clone->unregister($name);

        return $clone;
    }
}
