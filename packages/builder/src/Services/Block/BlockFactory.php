<?php

declare(strict_types=1);

namespace Moox\Builder\Services\Block;

use Illuminate\Support\Facades\DB;

class BlockFactory
{
    public function createFromBuild(string $blockClass, int $entityId): ?object
    {
        $blockOptions = DB::table('builder_entity_blocks')
            ->where('entity_id', $entityId)
            ->where('block_class', $blockClass)
            ->first();

        if (! $blockOptions) {
            return null;
        }

        $options = json_decode($blockOptions->options, true);

        return $this->create($blockClass, $options);
    }

    public function create(string $blockClass, array $options = []): object
    {
        $reflection = new \ReflectionClass($blockClass);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        $constructorParams = [];
        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();
            $default = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;

            $value = $options[$name] ?? $default;

            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                $value = $this->castValue($value, $typeName);
            }

            $constructorParams[] = $value;
        }

        return new $blockClass(...$constructorParams);
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
