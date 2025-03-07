<?php

declare(strict_types=1);

namespace Moox\Connect\Support;

use RuntimeException;

final class EndpointResolver
{
    private array $endpoints = [];

    private array $parameters = [];

    private array $globalParameters = [];

    public function register(string $name, string $pattern): void
    {
        $this->validatePattern($pattern);
        $this->endpoints[$name] = $pattern;
    }

    public function resolve(string $name, array $parameters = []): string
    {
        if (! isset($this->endpoints[$name])) {
            throw new RuntimeException("Unknown endpoint: {$name}");
        }

        $pattern = $this->endpoints[$name];
        $mergedParams = array_merge(
            $this->globalParameters,
            $this->parameters[$name] ?? [],
            $parameters
        );

        return $this->replaceParameters($pattern, $mergedParams);
    }

    public function setParameters(string $name, array $parameters): void
    {
        $this->parameters[$name] = $parameters;
    }

    public function setGlobalParameters(array $parameters): void
    {
        $this->globalParameters = $parameters;
    }

    public function getPattern(string $name): ?string
    {
        return $this->endpoints[$name] ?? null;
    }

    private function validatePattern(string $pattern): void
    {
        if (! preg_match('/^[\/\w\-\{\}\:\.\?\=\&\[\]]+$/', $pattern)) {
            throw new RuntimeException(
                "Invalid endpoint pattern: {$pattern}"
            );
        }

        $params = $this->extractParameters($pattern);
        $duplicates = array_count_values($params);

        foreach ($duplicates as $param => $count) {
            if ($count > 1) {
                throw new RuntimeException(
                    "Duplicate parameter in pattern: {$param}"
                );
            }
        }
    }

    private function extractParameters(string $pattern): array
    {
        preg_match_all('/\{([\w\-]+)\}/', $pattern, $matches);

        return $matches[1];
    }

    private function replaceParameters(string $pattern, array $parameters): string
    {
        $requiredParams = $this->extractParameters($pattern);
        $missingParams = array_diff($requiredParams, array_keys($parameters));

        if (! empty($missingParams)) {
            throw new RuntimeException(
                'Missing required parameters: '.implode(', ', $missingParams)
            );
        }

        $endpoint = $pattern;
        foreach ($parameters as $key => $value) {
            $endpoint = str_replace("{{$key}}", (string) $value, $endpoint);
        }

        return $endpoint;
    }

    public function batch(string $name, array $parameterSets): array
    {
        if (! isset($this->endpoints[$name])) {
            throw new RuntimeException("Unknown endpoint: {$name}");
        }

        return array_map(
            fn ($params) => $this->resolve($name, $params),
            $parameterSets
        );
    }

    public function clear(): void
    {
        $this->endpoints = [];
        $this->parameters = [];
        $this->globalParameters = [];
    }
}
