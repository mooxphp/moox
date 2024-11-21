<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Block;

class BlockFactory
{
    public function createFromBuild(string $blockClass, int $entityId): ?object
    {
        return $this->create($blockClass);
    }

    public function create(string $blockClass, array $options = []): object
    {
        $reflection = new \ReflectionClass($blockClass);
        $constructor = $reflection->getConstructor();

        if (! $constructor) {
            return $reflection->newInstance();
        }

        $params = $constructor->getParameters();
        $constructorParams = [];

        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();
            $default = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            $constructorParams[] = $options[$name] ?? $default;
        }

        return $reflection->newInstanceArgs($constructorParams);
    }

    protected function castValue($value, string $type)
    {
        return match ($type) {
            'bool' => (bool) $value,
            'int' => (int) $value,
            'string' => (string) $value,
            'array' => (array) $value,
            default => $value
        };
    }
}
