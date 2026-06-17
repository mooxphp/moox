<?php

declare(strict_types=1);

namespace Moox\Builder\Registry;

use Moox\Builder\Exceptions\UnknownFieldTypeException;
use Moox\Builder\FieldTypes\FieldType;

class FieldTypeRegistry
{
    /**
     * @var array<string, FieldType>
     */
    protected array $types = [];

    public function register(FieldType $type): void
    {
        $this->types[$type::key()] = $type;
    }

    public function get(string $key): FieldType
    {
        if (! isset($this->types[$key])) {
            throw UnknownFieldTypeException::forKey($key);
        }

        return $this->types[$key];
    }

    /**
     * @return array<string, FieldType>
     */
    public function all(): array
    {
        return $this->types;
    }

    /**
     * @return array<string, string>
     */
    public function optionsForSelect(): array
    {
        $options = [];

        foreach ($this->types as $key => $type) {
            $options[$key] = $type->label();
        }

        asort($options);

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function optionsForSubFields(): array
    {
        $options = [];

        foreach ($this->types as $key => $type) {
            if ($type->isLayoutMarker() || $type->hasSubFields()) {
                continue;
            }

            $options[$key] = $type->label();
        }

        asort($options);

        return $options;
    }
}
