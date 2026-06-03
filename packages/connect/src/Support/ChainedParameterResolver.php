<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use Illuminate\Support\Collection;
use RuntimeException;

final class ChainedParameterResolver
{
    private array $sourceData;

    private array $mapping;

    public function __construct(array $mapping)
    {
        $this->validateMapping($mapping);
        $this->mapping = $mapping;
    }

    public function resolve(array $sourceData): array
    {
        $this->sourceData = $sourceData;
        $resolved = [];

        foreach ($this->mapping as $map) {
            $sourceField = $map['source_field'];
            $targetParam = $map['target_param'];
            $transform = $map['transform'] ?? null;

            $values = $this->extractValues($sourceField);

            if ($transform !== null) {
                $values = array_map($transform, $values);
            }

            if (str_contains($targetParam, '{')) {
                $resolved[] = $this->createParameterSets($targetParam, $values);
            } else {
                $resolved[$targetParam] = $values[0] ?? null;
            }
        }

        return $this->flattenParameterSets($resolved);
    }

    private function extractValues(string $path): array
    {
        $segments = explode('.', $path);
        $current = $this->sourceData;

        foreach ($segments as $segment) {
            if ($segment === '[]') {
                if (! is_array($current)) {
                    throw new RuntimeException("Expected array at path segment: {$segment}");
                }

                return Collection::make($current)
                    ->flatten()
                    ->filter()
                    ->values()
                    ->all();
            }

            if (preg_match('/\[(\d+)\]/', $segment, $matches)) {
                $index = (int) $matches[1];
                $current = $current[$index] ?? null;
            } else {
                $current = $current[$segment] ?? null;
            }

            if ($current === null) {
                return [];
            }
        }

        return is_array($current) ? $current : [$current];
    }

    private function createParameterSets(string $template, array $values): array
    {
        return array_map(function ($value) use ($template) {
            return preg_replace('/\{[^}]+\}/', (string) $value, $template);
        }, $values);
    }

    private function flattenParameterSets(array $resolved): array
    {
        $result = [];
        $templates = array_filter($resolved, 'is_array');
        $fixed = array_filter($resolved, fn ($v) => ! is_array($v));

        if (empty($templates)) {
            return $fixed;
        }

        $combinations = $this->cartesianProduct($templates);

        foreach ($combinations as $combination) {
            $result[] = array_merge($fixed, $combination);
        }

        return $result;
    }

    private function cartesianProduct(array $arrays): array
    {
        $result = [[]];

        foreach ($arrays as $key => $array) {
            $temp = [];
            foreach ($result as $product) {
                foreach ($array as $item) {
                    $temp[] = $product + [$key => $item];
                }
            }
            $result = $temp;
        }

        return $result;
    }

    private function validateMapping(array $mapping): void
    {
        foreach ($mapping as $map) {
            if (! isset($map['source_field'], $map['target_param'])) {
                throw new RuntimeException(
                    'Parameter mapping must contain source_field and target_param'
                );
            }

            if (isset($map['transform']) && ! is_callable($map['transform'])) {
                throw new RuntimeException(
                    'Transform must be a callable'
                );
            }
        }
    }

    public function withMapping(array $mapping): self
    {
        $clone = clone $this;
        $clone->validateMapping($mapping);
        $clone->mapping = $mapping;

        return $clone;
    }
}
